<?php


use crazedsanity\core\ToolBox;
//ini_set('display_errors', 'true');
//ToolBox::$debugPrintOpt=1;


if(!isset($admin_id)) {
	$admin_id = 0;
}

$access = false;
if($admin_id > 0 && ( $acl->access($_SESSION['MM_Username'], 'accounts', 0, EDIT) )) {
	$access = true;
} else if($admin_id <= 0 && ( $acl->access($_SESSION['MM_Username'], 'accounts', 0, ADD) )) {
	$access = true;
}

if($access) {

	if(isset($_POST["group"])) {

		debugPrint($_POST, "POSTed data");
		// Save Page
		if(!empty($_POST['group_id'])) { // Update
			$group_id = $_POST["group_id"];
			$db->update("groups", $_POST["group"], "group_id=" . $group_id);
			addAlert("Success", "Record has been updated");

			$sql = "DELETE FROM acl WHERE group_id = {$group_id}";
			$db->query($sql);
		} else {
			// Insert
			// Unset the hidden group_id
			unset($_POST["group_id"]);
			$group_id = $db->insert("groups", $_POST["group"]);
//			mysql_insert_id();
			addAlert("Success", "Group #". $group_id ." has been added");
		}

		// Set permissions
		foreach ($_POST['perm'] as $asset => $perm) {
			foreach ($perm as $asset_id => $values) {
				foreach ($values as $level => $value) {
					if($value == '1') {
						$sql = "INSERT INTO acl SET group_id = {$group_id}, asset = '{$asset}', permission = {$level}, asset_id = {$asset_id}";
						$db->query($sql);
					}
				}
			}
		}
		debugPrint($db, "Database");
		$goHere = '/update/accounts/groups';
		if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "Redirect link")) {
			ToolBox::conditional_header($goHere);
		}
		exit;
		
	} else {
		if(isset($_GET['group_id'])) {
			$group_id = $_GET['group_id'];
		} else {
			$group_id = 0;
		}
	}

	$sql = "SELECT acl_id, user_id, group_id, asset, asset_id, permission FROM acl WHERE group_id = {$group_id}";
	$rs = $db->fetch_array($sql);

	$perms = array();
	if(count($rs) > 0) {
		foreach ($rs as $row) {
			$perms[$row['asset']][$row['asset_id']][$row['permission']] = 1;
		}
	}

	$sql = "SELECT * FROM groups WHERE group_id='{$group_id}'";
	$rs = $db->query_first($sql);
}


$_TEMPLATE['PAGE_TITLE'] = 'Groups';
$_tmpl = getTemplate('update/accounts/groups/item.tmpl');

if(is_array($rs)) {
	$_tmpl->addVarList($rs);
}
$_level = $_tmpl->setBlockRow('level');
$_asset = $_tmpl->setBlockRow('asset');


if($access) {
	
	
	$levels = array(4, 6, 7);
	$sql = <<<SQL
		SELECT asset_id, name, location, clean_name, sort
		FROM assets
		ORDER BY clean_name ASC
SQL;
	
	$assets = $db->fetch_array($sql);
	debugPrint($assets, "Assets");

	$output = '';
	foreach ($assets as $asset) {
		$renderedLevel = "";
		foreach ($levels as $level) {
			$selectedText = !empty($perms[$asset['name']][0][$level]) ? 'checked="checked"' : '';
			$_level->addVar('selectedText', $selectedText);
			$_level->addVar('level', $level);
			$_level->addVarList($asset);
			$renderedLevel .= $_level->render();
		}
		$_asset->addVarList($asset);
		$_asset->addVar($_level->name, $renderedLevel);
		$output .= $_asset->render();
	}
	
	$_tmpl->addVar($_asset->name, $output);
	
	echo $_tmpl->render(true);
	
} else {
	echo $acl->accessDeniedMsg();
}

