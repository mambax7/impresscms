<?php
// $Id$
/**
 * Administration of comments, Admin Header file
 *
 * Checks the rights of the user for being able to admin the comments
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	Administration
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id$
 */

include '../../../../mainfile.php';
include ICMS_ROOT_PATH.'/include/cp_functions.php';
if (is_object($icmsUser)) {
	$module_handler = icms::handler('icms_module');
	$icmsModule =& $module_handler->getByDirname('system');
	if (!in_array(XOOPS_GROUP_ADMIN, $icmsUser->getGroups())) {
		$sysperm_handler = icms::handler('icms_member_groupperm');
		if (!$sysperm_handler->checkRight('system_admin', XOOPS_SYSTEM_COMMENT, $icmsUser->getGroups())) {
			redirect_header(XOOPS_URL.'/', 3, _NOPERM);;
			exit();
		}
	}
} else {
	redirect_header(XOOPS_URL.'/', 3, _NOPERM);
	exit();
}

?>