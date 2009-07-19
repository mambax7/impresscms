<?php
/**
* Admin page to manage tribeusers
*
* List, add, edit and delete tribeuser objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Jan Pedersen, Marcello Brandao, Sina Asghari, Gustavo Pilla <contact@impresscms.org>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Tribeuser
 *
 * @param int $tribeuser_id Tribeuserid to be edited
*/
function edittribeuser($tribeuser_id = 0)
{
	global $profile_tribeuser_handler, $icmsModule, $icmsAdminTpl;

	$tribeuserObj = $profile_tribeuser_handler->get($tribeuser_id);

	if (!$tribeuserObj->isNew()){
		$icmsModule->displayAdminMenu(0, _AM_PROFILE_TRIBEUSERS . " > " . _CO_ICMS_EDITING);
		$sform = $tribeuserObj->getForm(_AM_PROFILE_TRIBEUSER_EDIT, 'addtribeuser');
		$sform->assign($icmsAdminTpl);

	} else {
		$icmsModule->displayAdminMenu(0, _AM_PROFILE_TRIBEUSERS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $tribeuserObj->getForm(_AM_PROFILE_TRIBEUSER_CREATE, 'addtribeuser');
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->display('db:profile_admin_tribeuser.html');
}

include_once("admin_header.php");

$profile_tribeuser_handler = icms_getModuleHandler('tribeuser');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addtribeuser','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_tribeuser_id = isset($_GET['tribeuser_id']) ? (int) $_GET['tribeuser_id'] : 0 ;

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op,$valid_op,true)){
  switch ($clean_op) {
  	case "mod":
  	case "changedField":

  		icms_cp_header();

  		edittribeuser($clean_tribeuser_id);
  		break;
  	case "addtribeuser":
          include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_tribeuser_handler);
  		$controller->storeFromDefaultForm(_AM_PROFILE_TRIBEUSER_CREATED, _AM_PROFILE_TRIBEUSER_MODIFIED);

  		break;

  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_tribeuser_handler);
  		$controller->handleObjectDeletion();

  		break;

  	case "view" :
  		$tribeuserObj = $profile_tribeuser_handler->get($clean_tribeuser_id);

  		icms_cp_header();
  		smart_adminMenu(1, _AM_PROFILE_TRIBEUSER_VIEW . ' > ' . $tribeuserObj->getVar('tribeuser_name'));

  		smart_collapsableBar('tribeuserview', $tribeuserObj->getVar('tribeuser_name') . $tribeuserObj->getEditTribeuserLink(), _AM_PROFILE_TRIBEUSER_VIEW_DSC);

  		$tribeuserObj->displaySingleObject();

  		smart_close_collapsable('tribeuserview');

  		break;

  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(0, _AM_PROFILE_TRIBEUSERS);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_tribeuser_handler);
  		$objectTable->addColumn(new IcmsPersistableColumn(''));

  		$icmsAdminTpl->assign('profile_tribeuser_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_tribeuser.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>