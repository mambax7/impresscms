<?php
/**
 * Creates Zipfiles
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 *
 * @category	ICMS
 * @package		Core
 * @version		SVN: $Id: ZipFileHandler.php 12310 2013-09-13 21:33:58Z skenow $
 */

/**
 * Zip file creation class.
 * Makes zip files.
 * package::i.tools
 *
 * php-downloader	v1.0	-	www.ipunkt.biz
 *
 * Based on :
 *
 *  http://www.zend.com/codex.php?id=535&single=1
 *  By Eric Mueller <eric@themepark.com>
 *
 *  http://www.zend.com/codex.php?id=470&single=1
 *  by Denis125 <webmaster@atlant.ru>
 *
 *  a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
 *  date and time of the compressed file
 *
 * Official ZIP file format: http://www.pkware.com/appnote.txt
 *
 * @copyright	(c)	2002 - www.ipunkt.biz (rok)
 *
 * @access		public
 * @category	ICMS
 * @package     Core
 */
class icms_file_ZipFileHandler
{
    /**
     * Array to store compressed data
     *
     * @var  array    $datasec
     */
    private $datasec      = [];

    /**
     * Central directory
     *
     * @var  array    $ctrl_dir
     */
    private $ctrl_dir     = [];

    /**
     * End of central directory record
     *
     * @var  string   $eof_ctrl_dir
     */
    private $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /**
     * Last offset position
     *
     * @var  integer  $old_offset
     */
    private $old_offset   = 0;

    /**
     * Converts an Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     *
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    private function unix2DosTime($unixtime = 0)
    {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
        ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } // end of the 'unix2DosTime()' method

    /**
     * Adds "file" to archive
     *
     * @param  string   file contents
     * @param  string   name of the file in the archive (may contains the path)
     * @param  integer  the current timestamp
     *
     * @access public
     */
    public function addFile($data, $name, $time = 0)
    {
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex(self::unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
        . '\x' . $dtime[4] . $dtime[5]
        . '\x' . $dtime[2] . $dtime[3]
        . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;
        $new_offset        = strlen(implode('', $this->datasec));

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name)) ; // length of filename
        $cdrec .= pack('v', 0) ;             // extra field length
        $cdrec .= pack('v', 0) ;             // file comment length
        $cdrec .= pack('v', 0) ;             // disk number start
        $cdrec .= pack('v', 0) ;             // internal file attributes
        $cdrec .= pack('V', 32) ;            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset) ; // relative offset of local header
        $this -> old_offset = $new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    } // end of the 'addFile()' method

    /**
     * Dumps out file
     *
     * @return  string  the zipped file
     *
     * @access public
     */
    public function file()
    {
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data
            . $ctrldir
            . $this -> eof_ctrl_dir
            . pack('v', count($this -> ctrl_dir))   // total # of entries "on this disk"
            . pack('v', count($this -> ctrl_dir))   // total # of entries overall
            . pack('V', strlen($ctrldir))           // size of central dir
            . pack('V', strlen($data))              // offset to start of central dir
            . "\x00\x00";                           // .zip file comment length
    } // end of the 'file()' method
} // end of the 'zipfile' class
