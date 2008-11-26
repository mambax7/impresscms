<?php
// $Id: blockform.php 1029 2007-09-09 03:49:25Z phppp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //


include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

$form = new XoopsThemeForm ( $block ['form_title'], 'blockform', 'admin.php', "post", true );

if ($block ['is_custom']) {
	global $editor, $xoopsConfig;
	$form->addElement ( new XoopsFormSelectEditor ( $form, "editor", $editor ) );
}

if (isset ( $block ['name'] )) {
	$form->addElement ( new XoopsFormLabel ( _AM_NAME, $block ['name'] ) );
}

# Adding dynamic block area/position system - TheRpLima - 2007-10-21
/*
$side_select = new XoopsFormSelect(_AM_BLKTYPE, "bside", $block['side']);
$side_select->addOptionArray(array(0 => _AM_SBLEFT, 1 => _AM_SBRIGHT, 3 => _AM_CBLEFT, 4 => _AM_CBRIGHT, 5 => _AM_CBCENTER, 7 => _AM_CBBOTTOMLEFT, 8 => _AM_CBBOTTOMRIGHT, 9 => _AM_CBBOTTOM, ));
*/
$posarr = XoopsBlock::getBlockPositions ( true );
$arr = array ( );
foreach ( $posarr as $k => $v ) {
	$tit = (defined ( $posarr [$k] ['title'] )) ? constant ( $posarr [$k] ['title'] ) : $posarr [$k] ['title'];
	$arr [$k] = $tit;
}
$side_select = new XoopsFormSelect ( _AM_BLKTYPE, "bside", $block ['side'] );
$side_select->addOptionArray ( $arr );
#


$form->addElement ( $side_select );
$form->addElement ( new XoopsFormText ( _AM_WEIGHT, "bweight", 2, 5, $block ['weight'] ) );
$form->addElement ( new XoopsFormRadioYN ( _AM_VISIBLE, 'bvisible', intval ( $block ['visible'] ) ) );

$page_handler = & xoops_gethandler ( 'page' );
$visible_tray1 = new XoopsFormElementTray ( _AM_VISIBLEIN, '' );
$visible_label = new XoopsFormLabel ( '', '<select name="bmodule[]" id="bmodule[]" multiple="multiple" size="5">' . $page_handler->getPageSelOptions ( $block ['modules'] ) . '</select>' );
$visible_tray1->addElement ( $visible_label );
$form->addElement ( $visible_tray1 );
$form->addElement ( new XoopsFormText ( _AM_TITLE, 'btitle', 50, 255, $block ['title'] ), false );
if ($block ['is_custom']) {
	if (! is_null ( $editor )) {
		$textarea = new XoopsFormDhtmlTextArea ( _AM_CONTENT, 'bcontent', $block ['content'], 15, 70, "xoopsHiddenText", array ('editor' => $editor ) );
	} else {
		$textarea = new XoopsFormDhtmlTextArea ( _AM_CONTENT, 'bcontent', $block ['content'], 15, 70, "xoopsHiddenText", array ('editor' => $xoopsConfig ['editor_default'] ) );
	}
	$textarea->setDescription ( '<span style="font-size:x-small;font-weight:bold;">' . _AM_USEFULTAGS . '</span><br /><span style="font-size:x-small;font-weight:normal;">' . sprintf ( _AM_BLOCKTAG1, '{X_SITEURL}', XOOPS_URL . '/' ) . '</span>' );
	$form->addElement ( $textarea, true );
	$ctype_select = new XoopsFormSelect ( _AM_CTYPE, 'bctype', $block ['ctype'] );
	$ctype_select->addOptionArray ( array ('H' => _AM_HTML, 'P' => _AM_PHP, 'S' => _AM_AFWSMILE, 'T' => _AM_AFNOSMILE ) );
	$form->addElement ( $ctype_select );
} else {
	if ($block ['template'] != '') {
		$tplfile_handler = & xoops_gethandler ( 'tplfile' );
		$btemplate = & $tplfile_handler->find ( $GLOBALS ['xoopsConfig'] ['template_set'], 'block', $block ['bid'] );
		if (count ( $btemplate ) > 0) {
			$form->addElement ( new XoopsFormLabel ( _AM_CONTENT, '<a href="' . XOOPS_URL . '/modules/system/admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $btemplate [0]->getVar ( 'tpl_id' ) . '">' . _AM_EDITTPL . '</a>' ) );
		} else {
			$btemplate2 = & $tplfile_handler->find ( 'default', 'block', $block ['bid'] );
			if (count ( $btemplate2 ) > 0) {
				$form->addElement ( new XoopsFormLabel ( _AM_CONTENT, '<a href="' . XOOPS_URL . '/modules/system/admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $btemplate2 [0]->getVar ( 'tpl_id' ) . '" rel="external">' . _AM_EDITTPL . '</a>' ) );
			}
		}
	}
	if ($block ['edit_form'] != false) {
		$form->addElement ( new XoopsFormLabel ( _AM_OPTIONS, $block ['edit_form'] ) );
	}
}
$cache_select = new XoopsFormSelect ( _AM_BCACHETIME, 'bcachetime', $block ['cachetime'] );
$cache_select->addOptionArray ( array ('0' => _NOCACHE, '30' => sprintf ( _SECONDS, 30 ), '60' => _MINUTE, '300' => sprintf ( _MINUTES, 5 ), '1800' => sprintf ( _MINUTES, 30 ), '3600' => _HOUR, '18000' => sprintf ( _HOURS, 5 ), '86400' => _DAY, '259200' => sprintf ( _DAYS, 3 ), '604800' => _WEEK, '2592000' => _MONTH ) );
$form->addElement ( $cache_select );
if (isset ( $block ['bid'] )) {
	$form->addElement ( new XoopsFormHidden ( 'bid', $block ['bid'] ) );
}
$form->addElement ( new XoopsFormHidden ( 'op', $block ['op'] ) );
$form->addElement ( new XoopsFormHidden ( 'fct', 'blocksadmin' ) );
$button_tray = new XoopsFormElementTray ( '', '&nbsp;' );
if ($block ['is_custom']) {
	$button_tray->addElement ( new XoopsFormButton ( '', 'previewblock', _PREVIEW, "submit" ) );
}
$button_tray->addElement ( new XoopsFormButton ( '', 'submitblock', _SUBMIT, "submit" ) );
$btn = new XoopsFormButton ( '', 'reset', _CANCEL, 'button' );

global $impresscms, $op;
if ($op == 'edit') {
	$onclick = "location='" . $impresscms->urls['previouspage'] . "'";
	$btn->setExtra ( 'onclick="' . $onclick . '"' );
} else {
	$btn->setExtra ( 'onclick="document.getElementById(\'new\').style.display = \'none\'; return false;"' );
}
$button_tray->addElement ( $btn );
$form->addElement ( $button_tray );
?>