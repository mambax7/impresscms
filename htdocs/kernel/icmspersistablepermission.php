<?php
if (!defined("ICMS_ROOT_PATH")) die("ImpressCMS root path not defined");

class IcmsPersistablePermissionHandler extends icms_ipf_permission_Handler {
	private $_deprecated;
	public function __construct() {
		parent::getInstance();
		$this->_deprecated = icms_deprecated('icms_ipf_permission_Handler', 'This will be removed in version 1.4');
	}
}
?>