<?php
/**
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

if ( !is_object($xoopsUser) || !is_object($xoopsModule) || !$xoopsUser->isAdmin($xoopsModule->mid()) ) {
    exit("Access Denied");
}
include_once XOOPS_ROOT_PATH."/modules/system/admin/modulesadmin/modulesadmin.php";
require_once XOOPS_ROOT_PATH."/class/xoopslists.php";
if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_GET['op']))?trim(StopXSS($_GET['op'])):((isset($_POST['op']))?trim(StopXSS($_POST['op'])):'list');


if (in_array($op, array('submit', 'install_ok', 'update_ok', 'uninstall_ok'))) {
	if (!$GLOBALS['xoopsSecurity']->check()) {
		$op = 'list';
	}
}

if ( $op == "list" ) {
	xoops_cp_header();
	echo xoops_module_list();
	xoops_cp_footer();
	exit();
}

if ( $op == "confirm" ) {
	xoops_cp_header();
	//OpenTable();
	$error = array();
	if ( !is_writable(XOOPS_CACHE_PATH.'/') ) {
		// attempt to chmod 666
		if ( !chmod(XOOPS_CACHE_PATH.'/', 0777) ) {
			$error[] = sprintf(_MUSTWABLE, "<b>".XOOPS_CACHE_PATH.'/</b>');
		}
	}

	if ( count($error) > 0 ) {
		xoops_error($error);
		echo "<p><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a></p>";
		xoops_cp_footer();
		exit();
	}

	echo "<h4 style='text-align:"._GLOBAL_LEFT.";'>"._MD_AM_PCMFM."</h4>
	<form action='admin.php' method='post'>
	<input type='hidden' name='fct' value='modulesadmin' />
	<input type='hidden' name='op' value='submit' />
	<table width='100%' border='0' cellspacing='1' class='outer'>
	<tr align='center'><th>"._MD_AM_MODULE."</th><th>"._MD_AM_ACTION."</th><th>"._MD_AM_ORDER."</th></tr>";
	$mcount = 0;
	$myts =& MyTextsanitizer::getInstance();
	foreach ($module as $mid) {
		if ($mcount % 2 != 0) {
			$class = 'odd';
		} else {
			$class = 'even';
		}
		echo '<tr class="'.$class.'"><td align="center">'.$myts->stripSlashesGPC($oldname[$mid]);
		$newname[$mid] = trim($myts->stripslashesGPC($newname[$mid]));
		if ($newname[$mid] != $oldname[$mid]) {
			echo '&nbsp;&raquo;&raquo;&nbsp;<span style="color:#ff0000;font-weight:bold;">'.$newname[$mid].'</span>';
		}
		echo '</td><td align="center">';
		if (isset($newstatus[$mid]) && $newstatus[$mid] ==1) {
			if ($oldstatus[$mid] == 0) {
				echo "<span style='color:#ff0000;font-weight:bold;'>"._MD_AM_ACTIVATE."</span>";
			} else {
				echo _MD_AM_NOCHANGE;
			}
		} else {
			$newstatus[$mid] = 0;
			if ($oldstatus[$mid] == 1) {
				echo "<span style='color:#ff0000;font-weight:bold;'>"._MD_AM_DEACTIVATE."</span>";
			} else {
				echo _MD_AM_NOCHANGE;
			}
		}
		echo "</td><td align='center'>";
		if ($oldweight[$mid] != $weight[$mid]) {
			echo "<span style='color:#ff0000;font-weight:bold;'>".$weight[$mid]."</span>";
		} else {
			echo $weight[$mid];
		}
		echo "
		<input type='hidden' name='module[]' value='".intval($mid)."' />
		<input type='hidden' name='oldname[".$mid."]' value='".htmlspecialchars($oldname[$mid], ENT_QUOTES)."' />
		<input type='hidden' name='newname[".$mid."]' value='".htmlspecialchars($newname[$mid], ENT_QUOTES)."' />
		<input type='hidden' name='oldstatus[".$mid."]' value='".intval($oldstatus[$mid])."' />
		<input type='hidden' name='newstatus[".$mid."]' value='".intval($newstatus[$mid])."' />
		<input type='hidden' name='oldweight[".$mid."]' value='".intval($oldweight[$mid])."' />
		<input type='hidden' name='weight[".$mid."]' value='".intval($weight[$mid])."' />
		</td></tr>";
	}

	echo "
	<tr class='foot' align='center'><td colspan='3'><input type='submit' value='"._MD_AM_SUBMIT."' />&nbsp;<input type='button' value='"._MD_AM_CANCEL."' onclick='location=\"admin.php?fct=modulesadmin\"' />".$GLOBALS['xoopsSecurity']->getTokenHTML()."</td></tr>
	</table>
	</form>";
	xoops_cp_footer();
	exit();
}

if ( $op == "submit" ) {
	$ret = array();
	$write = false;
	foreach ($module as $mid) {
		if (isset($newstatus[$mid]) && $newstatus[$mid] ==1) {
			if ($oldstatus[$mid] == 0) {
				$ret[] = xoops_module_activate($mid);
			}
		} else {
			if ($oldstatus[$mid] == 1) {
				$ret[] = xoops_module_deactivate($mid);
			}
		}
		$newname[$mid] = trim($newname[$mid]);
		if ($oldname[$mid] != $newname[$mid] || $oldweight[$mid] != $weight[$mid]) {
			$ret[] = xoops_module_change($mid, $weight[$mid], $newname[$mid]);
			$write = true;
		}
		flush();
	}
	if ( $write ) {
		$contents = xoops_module_get_admin_menu();
		if (!xoops_module_write_admin_menu($contents)) {
			$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
		}
	}
	xoops_cp_header();
	if ( count($ret) > 0 ) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

if ($op == 'install') {
	$module_handler =& xoops_gethandler('module');
	$mod =& $module_handler->create();
	$mod->loadInfoAsVar($module);
	if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="'.XOOPS_URL.'/modules/'.$mod->getVar('dirname').'/'.trim($mod->getInfo('image')).'" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">'.$mod->getVar('name').'</span><br /><br />'._MD_AM_RUSUREINS;
	xoops_cp_header();
	xoops_confirm(array('module' => $module, 'op' => 'install_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _MD_AM_INSTALL);
	xoops_cp_footer();
	exit();
}

if ($op == 'install_ok') {
	$ret = array();
	$ret[] = xoops_module_install($module);
	$contents = xoops_module_get_admin_menu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
	}
	xoops_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

if ($op == 'uninstall') {
	$module_handler =& xoops_gethandler('module');
	$mod =& $module_handler->getByDirname($module);
	if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="'.XOOPS_URL.'/modules/'.$mod->getVar('dirname').'/'.trim($mod->getInfo('image')).'" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">'.$mod->getVar('name').'</span><br /><br />'._MD_AM_RUSUREUNINS;
	xoops_cp_header();
	xoops_confirm(array('module' => $module, 'op' => 'uninstall_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _YES);
	xoops_cp_footer();
	exit();
}

if ($op == 'uninstall_ok') {
	$ret = array();
	$ret[] = xoops_module_uninstall($module);
	$contents = xoops_module_get_admin_menu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
	}
	xoops_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

if ($op == 'update') {
	$module_handler =& xoops_gethandler('module');
	$mod =& $module_handler->getByDirname($module);
	if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="'.XOOPS_URL.'/modules/'.$mod->getVar('dirname').'/'.trim($mod->getInfo('image')).'" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">'.$mod->getVar('name').'</span><br /><br />'._MD_AM_RUSUREUPD;
	xoops_cp_header();
	xoops_confirm(array('dirname' => $module, 'op' => 'update_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _MD_AM_UPDATE);
	xoops_cp_footer();
	exit();
}

if ($op == 'update_ok') {
	$dirname = trim($dirname);
	$module_handler =& xoops_gethandler('module');
	$module =& $module_handler->getByDirname($dirname);

	// Save current version for use in the update function
	$prev_version = $module->getVar('version');
	$prev_dbversion = $module->getVar('dbversion');
	include_once XOOPS_ROOT_PATH.'/class/template.php';
	xoops_template_clear_module_cache($module->getVar('mid'));
	// we dont want to change the module name set by admin
	$temp_name = $module->getVar('name');
	$module->loadInfoAsVar($dirname);
	$module->setVar('name', $temp_name);
	xoops_cp_header();
	if (!$module_handler->insert($module)) {
		echo '<p>Could not update '.$module->getVar('name').'</p>';
		echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	} else {
		$newmid = $module->getVar('mid');
		$msgs = array();
		$msgs[] = _MD_AM_MOD_DATA_UPDATED;
		$tplfile_handler =& xoops_gethandler('tplfile');
		$deltpl =& $tplfile_handler->find('default', 'module', $module->getVar('mid'));
		$delng = array();
		if (is_array($deltpl)) {
			$xoopsTpl = new XoopsTpl();
			// clear cache files
			$xoopsTpl->clear_cache(null, 'mod_'.$dirname);
			// delete template file entry in db
			$dcount = count($deltpl);
			for ($i = 0; $i < $dcount; $i++) {
				if (!$tplfile_handler->delete($deltpl[$i])) {
					$delng[] = $deltpl[$i]->getVar('tpl_file');
				}
			}
		}

		$templates = $module->getInfo('templates');
		if ($templates != false) {
			$msgs[] = _MD_AM_MOD_UP_TEM;
			foreach ($templates as $tpl) {
				$tpl['file'] = trim($tpl['file']);
				if (!in_array($tpl['file'], $delng)) {
					$tpldata =& xoops_module_gettemplate($dirname, $tpl['file']);
					$tplfile =& $tplfile_handler->create();
					$tplfile->setVar('tpl_refid', $newmid);
					$tplfile->setVar('tpl_lastimported', 0);
					$tplfile->setVar('tpl_lastmodified', time());
					if (preg_match("/\.css$/i", $tpl['file'])) {
						$tplfile->setVar('tpl_type', 'css');
					} else {
						$tplfile->setVar('tpl_type', 'module');
					}
					$tplfile->setVar('tpl_source', $tpldata, true);
					$tplfile->setVar('tpl_module', $dirname);
					$tplfile->setVar('tpl_tplset', 'default');
					$tplfile->setVar('tpl_file', $tpl['file'], true);
					$tplfile->setVar('tpl_desc', $tpl['description'], true);
					if (!$tplfile_handler->insert($tplfile)) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILINSTEMPFILE.'</span>', $tpl['file']);
					} else {
						$newid = $tplfile->getVar('tpl_id');
						$msgs[] = sprintf('&nbsp;&nbsp;<span>'._MD_AM_TEMPINS.'</span>', $tpl['file']);
						if ($xoopsConfig['template_set'] == 'default') {
							if (!xoops_template_touch($newid)) {
								$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_NOTRECOMPTEMPFILE.'</span>', $tpl['file']);
							} else {
								$msgs[] = sprintf('&nbsp;&nbsp;<span>'._MD_AM_RECOMPTEMPFILE.'</span>', $tpl['file']);
							}
						}
					}
					unset($tpldata);
				} else {
					$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_NOTDELTEMPFILE.'</span>', $tpl['file']);
				}
			}
		}

		$contents = xoops_module_get_admin_menu();
		if (!xoops_module_write_admin_menu($contents)) {
			$msgs[] = '<p><span style="color:#ff0000;">'._MD_AM_FAILWRITE.'</span></p>';
		}

		$blocks = $module->getInfo('blocks');
		$msgs[] = _MD_AM_MOD_REBUILD_BLOCKS;
		if ($blocks != false) {
			$count = count($blocks);
			$showfuncs = array();
			$funcfiles = array();
			for ( $i = 1; $i <= $count; $i++ ) {
				if (isset($blocks[$i]['show_func']) && $blocks[$i]['show_func'] != '' && isset($blocks[$i]['file']) && $blocks[$i]['file'] != '') {
					$editfunc = isset($blocks[$i]['edit_func']) ? $blocks[$i]['edit_func'] : '';
					$showfuncs[] = $blocks[$i]['show_func'];
					$funcfiles[] = $blocks[$i]['file'];
					$template = '';
					if ((isset($blocks[$i]['template']) && trim($blocks[$i]['template']) != '')) {
						$content =& xoops_module_gettemplate($dirname, $blocks[$i]['template'], true);
					}
					if (!$content) {
						$content = '';
					} else {
						$template = $blocks[$i]['template'];
					}
					$options = '';
					if (!empty($blocks[$i]['options'])) {
						$options = $blocks[$i]['options'];
					}
					$sql = "SELECT bid, name FROM ".$xoopsDB->prefix('newblocks')." WHERE mid='".intval($module->getVar('mid'))."' AND func_num='".intval($i)."' AND show_func='".addslashes($blocks[$i]['show_func'])."' AND func_file='".addslashes($blocks[$i]['file'])."'";
					$fresult = $xoopsDB->query($sql);
					$fcount = 0;
					while ($fblock = $xoopsDB->fetchArray($fresult)) {
						$fcount++;
						$sql = "UPDATE ".$xoopsDB->prefix("newblocks")." SET name='".addslashes($blocks[$i]['name'])."', edit_func='".addslashes($editfunc)."', content='', template='".$template."', last_modified=".time()." WHERE bid='".intval($fblock['bid'])."'";
						$result = $xoopsDB->query($sql);
						if (!$result) {
							$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_COULDNOTUPDATE,$fblock['name']);
						} else {
							$msgs[] = >sprintf('&nbsp;&nbsp;'._MD_AM_COULDNOTUPDATE,$fblock['name'],icms_conv_nr2local($fblock['bid']));
							if ($template != '') {
								$tplfile =& $tplfile_handler->find('default', 'block', $fblock['bid']);
								if (count($tplfile) == 0) {
									$tplfile_new =& $tplfile_handler->create();
									$tplfile_new->setVar('tpl_module', $dirname);
									$tplfile_new->setVar('tpl_refid', intval($fblock['bid']));
									$tplfile_new->setVar('tpl_tplset', 'default');
									$tplfile_new->setVar('tpl_file', $blocks[$i]['template'], true);
									$tplfile_new->setVar('tpl_type', 'block');
								}
								else {
									$tplfile_new = $tplfile[0];
								}
								$tplfile_new->setVar('tpl_source', $content, true);
								$tplfile_new->setVar('tpl_desc', $blocks[$i]['description'], true);
								$tplfile_new->setVar('tpl_lastmodified', time());
								$tplfile_new->setVar('tpl_lastimported', 0);
								if (!$tplfile_handler->insert($tplfile_new)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILUPDTEMP.'</span>', $blocks[$i]['template']);
								} else {
									$msgs[] = '&nbsp;&nbsp;Template <b>'.$blocks[$i]['template'].'</b> updated.';
									if ($xoopsConfig['template_set'] == 'default') {
										if (!xoops_template_touch($tplfile_new->getVar('tpl_id'))) {
											$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_NOTRECOMPTEMPFILE.'</span>', $blocks[$i]['template']);
										} else {
											$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_RECOMPTEMPFILE, $blocks[$i]['template']);
										}
									}
								}
							}
						}
					}

					if ($fcount == 0) {
						$newbid = $xoopsDB->genId($xoopsDB->prefix('newblocks').'_bid_seq');
						$block_name = addslashes($blocks[$i]['name']);
						$sql = "INSERT INTO ".$xoopsDB->prefix("newblocks")." (bid, mid, func_num, options, name, title, content, side, weight, visible, block_type, c_type, isactive, dirname, func_file, show_func, edit_func, template, bcachetime, last_modified) VALUES ('".intval($newbid)."', '".intval($module->getVar('mid'))."', '".intval($i)."','".addslashes($options)."','".$block_name."', '".$block_name."', '', '1', '0', '0', 'M', 'H', '1', '".addslashes($dirname)."', '".addslashes($blocks[$i]['file'])."', '".addslashes($blocks[$i]['show_func'])."', '".addslashes($editfunc)."', '".$template."', '0', '".time()."')";
						$result = $xoopsDB->query($sql);
						if (!$result) {
							$msgs[] = '&nbsp;&nbsp;ERROR: Could not create '.$blocks[$i]['name'];echo $sql;
						} else {
							if (empty($newbid)) {
								$newbid = $xoopsDB->getInsertId();
							}
							$groups =& $xoopsUser->getGroups();
							$gperm_handler =& xoops_gethandler('groupperm');
							foreach ($groups as $mygroup) {
								$bperm =& $gperm_handler->create();
								$bperm->setVar('gperm_groupid', intval($mygroup));
								$bperm->setVar('gperm_itemid', intval($newbid));
								$bperm->setVar('gperm_name', 'block_read');
								$bperm->setVar('gperm_modid', 1);
								if (!$gperm_handler->insert($bperm)) {
									$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not add block access right. Block ID: <b>'.$newbid.'</b> Group ID: <b>'.$mygroup.'</b></span>';
								} else {
									$msgs[] = '&nbsp;&nbsp;Added block access right. Block ID: <b>'.$newbid.'</b> Group ID: <b>'.$mygroup.'</b>';
								}
							}
							
							if ($template != '') {
								$tplfile =& $tplfile_handler->create();
								$tplfile->setVar('tpl_module', $dirname);
								$tplfile->setVar('tpl_refid', intval($newbid));
								$tplfile->setVar('tpl_source', $content, true);
								$tplfile->setVar('tpl_tplset', 'default');
								$tplfile->setVar('tpl_file', $blocks[$i]['template'], true);
								$tplfile->setVar('tpl_type', 'block');
								$tplfile->setVar('tpl_lastimported', 0);
								$tplfile->setVar('tpl_lastmodified', time());
								$tplfile->setVar('tpl_desc', $blocks[$i]['description'], true);
								if (!$tplfile_handler->insert($tplfile)) {
									$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert template <b>'.$blocks[$i]['template'].'</b> to the database.</span>';
								} else {
									$newid = $tplfile->getVar('tpl_id');
									$msgs[] = '&nbsp;&nbsp;Template <b>'.$blocks[$i]['template'].'</b> added to the database.';
									if ($xoopsConfig['template_set'] == 'default') {
										if (!xoops_template_touch($newid)) {
											$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Template <b>'.$blocks[$i]['template'].'</b> recompile failed.</span>';
										} else {
											$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_RECOMPTEMPFILE, $blocks[$i]['template']);
										}
									}
								}
							}
							$msgs[] = '&nbsp;&nbsp;Block <b>'.$blocks[$i]['name'].'</b> created. Block ID: <b>'.$newbid.'</b>';
							$sql = "INSERT INTO ".$xoopsDB->prefix('block_module_link')." (block_id, module_id, page_id) VALUES ('".intval($newbid)."', '0', '1')";
							$xoopsDB->query($sql);
						}
					}
				}
			}

			$icms_block_handler = xoops_gethandler('block');
			$block_arr = $icms_block_handler->getByModule($module->getVar('mid'));
			foreach ($block_arr as $block) {
				if (!in_array($block->getVar('show_func'), $showfuncs) || !in_array($block->getVar('func_file'), $funcfiles)) {
					$sql = sprintf("DELETE FROM %s WHERE bid = '%u'", $xoopsDB->prefix('newblocks'), intval($block->getVar('bid')));
					if(!$xoopsDB->query($sql)) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete block <b>'.$block->getVar('name').'</b>. Block ID: <b>'.$block->getVar('bid').'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Block <b>'.$block->getVar('name').' deleted. Block ID: <b>'.$block->getVar('bid').'</b>';
						if ($block->getVar('template') != '') {
							$tplfiles =& $tplfile_handler->find(null, 'block', $block->getVar('bid'));
							if (is_array($tplfiles)) {
								$btcount = count($tplfiles);
								for ($k = 0; $k < $btcount; $k++) {
									if (!$tplfile_handler->delete($tplfiles[$k])) {
										$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not remove deprecated block template. (ID: <b>'.$tplfiles[$k]->getVar('tpl_id').'</b>)</span>';
									} else {
										$msgs[] = '&nbsp;&nbsp;Block template <b>'.$tplfiles[$k]->getVar('tpl_file').'</b> deprecated.';
									}
								}
							}
						}
					}
				}
			}
		}

		// first delete all config entries
		$config_handler =& xoops_gethandler('config');
		$configs =& $config_handler->getConfigs(new Criteria('conf_modid', $module->getVar('mid')));
		$confcount = count($configs);
		$config_delng = array();
		if ($confcount > 0) {
			$msgs[] = 'Deleting module config options...';
			for ($i = 0; $i < $confcount; $i++) {
				if (!$config_handler->deleteConfig($configs[$i])) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete config data from the database. Config ID: <b>'.$configs[$i]->getvar('conf_id').'</b></span>';
					// save the name of config failed to delete for later use
					$config_delng[] = $configs[$i]->getvar('conf_name');
				} else {
					$config_old[$configs[$i]->getvar('conf_name')]['value'] = $configs[$i]->getvar('conf_value', 'N');
					$config_old[$configs[$i]->getvar('conf_name')]['formtype'] = $configs[$i]->getvar('conf_formtype');
					$config_old[$configs[$i]->getvar('conf_name')]['valuetype'] = $configs[$i]->getvar('conf_valuetype');
					$msgs[] = '&nbsp;&nbsp;Config data deleted from the database. Config ID: <b>'.$configs[$i]->getVar('conf_id').'</b>';
				}
			}
		}

		// now reinsert them with the new settings
		$configs = $module->getInfo('config');
		if ($configs != false) {
			if ($module->getVar('hascomments') != 0) {
				include_once(XOOPS_ROOT_PATH.'/include/comment_constants.php');
				array_push($configs, array('name' => 'com_rule', 'title' => '_CM_COMRULES', 'description' => '', 'formtype' => 'select', 'valuetype' => 'int', 'default' => 1, 'options' => array('_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, '_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, '_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, '_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN)));
				array_push($configs, array('name' => 'com_anonpost', 'title' => '_CM_COMANONPOST', 'description' => '', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 0));
			}
		} else {
			if ($module->getVar('hascomments') != 0) {
				$configs = array();
				include_once(XOOPS_ROOT_PATH.'/include/comment_constants.php');
				$configs[] = array('name' => 'com_rule', 'title' => '_CM_COMRULES', 'description' => '', 'formtype' => 'select', 'valuetype' => 'int', 'default' => 1, 'options' => array('_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, '_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, '_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, '_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN));
				$configs[] = array('name' => 'com_anonpost', 'title' => '_CM_COMANONPOST', 'description' => '', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 0);
			}
		}

		// RMV-NOTIFY
		if ($module->getVar('hasnotification') != 0) {
			if (empty($configs)) {
				$configs = array();
			}
			// Main notification options
			include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
			include_once XOOPS_ROOT_PATH . '/include/notification_functions.php';
			$options = array();
			$options['_NOT_CONFIG_DISABLE'] = XOOPS_NOTIFICATION_DISABLE;
			$options['_NOT_CONFIG_ENABLEBLOCK'] = XOOPS_NOTIFICATION_ENABLEBLOCK;
			$options['_NOT_CONFIG_ENABLEINLINE'] = XOOPS_NOTIFICATION_ENABLEINLINE;
			$options['_NOT_CONFIG_ENABLEBOTH'] = XOOPS_NOTIFICATION_ENABLEBOTH;

			//$configs[] = array ('name' => 'notification_enabled', 'title' => '_NOT_CONFIG_ENABLED', 'description' => '_NOT_CONFIG_ENABLEDDSC', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 1);
			$configs[] = array ('name' => 'notification_enabled', 'title' => '_NOT_CONFIG_ENABLE', 'description' => '_NOT_CONFIG_ENABLEDSC', 'formtype' => 'select', 'valuetype' => 'int', 'default' => XOOPS_NOTIFICATION_ENABLEBOTH, 'options'=>$options);
			// Event specific notification options
			// FIXME: for some reason the default doesn't come up properly
			//  initially is ok, but not when 'update' module..
			$options = array();
			$categories =& notificationCategoryInfo('',$module->getVar('mid'));
			foreach ($categories as $category) {
				$events =& notificationEvents ($category['name'], false, $module->getVar('mid'));
				foreach ($events as $event) {
					if (!empty($event['invisible'])) {
						continue;
					}
					$option_name = $category['title'] . ' : ' . $event['title'];
					$option_value = $category['name'] . '-' . $event['name'];
					$options[$option_name] = $option_value;
					//$configs[] = array ('name' => notificationGenerateConfig($category,$event,'name'), 'title' => notificationGenerateConfig($category,$event,'title_constant'), 'description' => notificationGenerateConfig($category,$event,'description_constant'), 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 1);
				}
			}
			$configs[] = array ('name' => 'notification_events', 'title' => '_NOT_CONFIG_EVENTS', 'description' => '_NOT_CONFIG_EVENTSDSC', 'formtype' => 'select_multi', 'valuetype' => 'array', 'default' => array_values($options), 'options' => $options);
		}

		if ($configs != false) {
			$msgs[] = 'Adding module config data...';
			$config_handler =& xoops_gethandler('config');
			$order = 0;
			foreach ($configs as $config) {
				// only insert ones that have been deleted previously with success
				if (!in_array($config['name'], $config_delng)) {
					$confobj =& $config_handler->createConfig();
					$confobj->setVar('conf_modid', intval($newmid));
					$confobj->setVar('conf_catid', 0);
					$confobj->setVar('conf_name', $config['name']);
					$confobj->setVar('conf_title', $config['title'], true);
					$confobj->setVar('conf_desc', $config['description'], true);
					$confobj->setVar('conf_formtype', $config['formtype']);
					$confobj->setVar('conf_valuetype', $config['valuetype']);
					if (isset($config_old[$config['name']]['value']) && $config_old[$config['name']]['formtype'] == $config['formtype'] && $config_old[$config['name']]['valuetype'] == $config['valuetype']) {
						// preserver the old value if any
						// form type and value type must be the same
						$confobj->setVar('conf_value', $config_old[$config['name']]['value'], true);
					} else {
						$confobj->setConfValueForInput($config['default'], true);

				    //$confobj->setVar('conf_value', $config['default'], true);
					}
					$confobj->setVar('conf_order', $order);
					$confop_msgs = '';
					if (isset($config['options']) && is_array($config['options'])) {
						foreach ($config['options'] as $key => $value) {
							$confop =& $config_handler->createConfigOption();
							$confop->setVar('confop_name', $key, true);
							$confop->setVar('confop_value', $value, true);
							$confobj->setConfOptions($confop);
							$confop_msgs .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;Config option added. Name: <b>'.$key.'</b> Value: <b>'.$value.'</b>';
							unset($confop);
						}
					}
					$order++;
					if (false != $config_handler->insertConfig($confobj)) {
						$msgs[] = '&nbsp;&nbsp;Config <b>'.$config['name'].'</b> added to the database.'.$confop_msgs;
					} else {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert config <b>'.$config['name'].'</b> to the database.</span>';
					}
					unset($confobj);
				}
			}
			unset($configs);
		}

		// execute module specific update script if any
		$update_script = $module->getInfo('onUpdate');
		$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
		if (false != $update_script && trim($update_script) != '') {
			include_once XOOPS_ROOT_PATH.'/modules/'.$dirname.'/'.trim($update_script);
			if (function_exists('xoops_module_update_'.$ModName)) {
				$func = 'xoops_module_update_'.$ModName;
				if (!$func($module, $prev_version, $prev_dbversion)) {
					$msgs[] = sprintf(_MD_AM_FAIL_EXEC, $func);
				} else {
					$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
				}
			}elseif (function_exists('icms_module_update_'.$ModName)) {
				$func = 'icms_module_update_'.$ModName;
				if (!$func($module, $prev_version, $prev_dbversion)) {
					$msgs[] = sprintf(_MD_AM_FAIL_EXEC, $func);
				} else {
					$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
				}
			}
		}

		foreach ($msgs as $msg) {
			echo '<code>'.$msg.'</code><br />';
		}
		echo "<p>".sprintf(_MD_AM_OKUPD, "<b>".$module->getVar('name')."</b>")."</p>";
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
}

?>