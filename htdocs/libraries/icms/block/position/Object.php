<?php
/**
 * Block Positions manager for the Impress Persistable Framework
 *
 * Longer description about this page
 *
 * @copyright      http://www.impresscms.org/ The ImpressCMS Project
 * @license         LICENSE.txt
 * @package	core
 * @since            1.0
 * @version		$Id: Blockposition.php 19514 2010-06-21 22:50:14Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * icms_block_position_Object
 *
 */
class icms_block_position_Object extends icms_ipf_Object {

	/**
	 * Constructor
	 *
	 * @param icms_block_position_Handler $handler
	 */
	public function __construct(& $handler) {

		parent::__construct($handler);

		$this->quickInitVar('id', XOBJ_DTYPE_INT);
		$this->quickInitVar('pname', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('description', XOBJ_DTYPE_TXTAREA);
		$this->quickInitVar('block_default', XOBJ_DTYPE_INT);
		$this->quickInitVar('block_type', XOBJ_DTYPE_TXTBOX);

	}

}

/**
 * IcmsBlockposition
 * @deprecated Use icms_block_position_Object, instead
 * @todo Remove in version 1.4 - all instances have been removed from the core
 */
class IcmsBlockposition extends icms_block_position_Object {
	public function __construct() {
		parent::__construct(&$handler);
		$this->setErrors(icms_deprecated('icms_block_position_Object'));
	}

}