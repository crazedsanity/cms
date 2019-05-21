<?php
$user->superadmin = 1;
$user->restrict();

use crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt=1;

if(isset($clean['user_id'])) {
	$user_id = intval($clean['user_id']);
}
else {
	$user_id = 0;
}

$access = false;
$errormsg = false;

if($user_id > 0 && ( $acl->access($_SESSION['MM_Username'], 'accounts', $user_id, EDIT) )) {
	$access = true;
}
else if($user_id <= 0 && ( $acl->access($_SESSION['MM_Username'], 'accounts', $user_id, ADD) )) {
	$access = true;
}

if($access) {

	if(isset($clean["admin"])) {
		
		debugPrint($_POST, "POSTed data");
		
		$goHere = '/update/accounts/';
		
		// trim off any extra spaces in the input.
		$failedPretest = "";
		$passCheck = $clean['admin']['password2'];
		unset($clean['admin']['password2']);
		if(!empty($passCheck)) {
			if($passCheck !== $clean['admin']['password']) {
				addAlert("Password Mismatch", "The password and password check fields must match.  Please try again", "error");
				ToolBox::conditional_header($goHere);
				exit;
			}
		}
		else {
			// not trying to update password
			unset($clean['admin']['password']);
		}
		
		// it's okay if email is blank, it's not required
		if(empty($clean['admin']['email'])) {
			unset($clean['admin']['email']);
		}
		foreach($clean['admin'] as $k=>$v) {
			$newVal = trim($v);
			$clean['admin'][$k] = $newVal;
			if(empty($newVal)) {
				$failedPretest = ToolBox::create_list($failedPretest, "$k");
			}
		}

		if(strlen($failedPretest) !== 0) {
			addAlert("Missing Information", "Your request was missing information for <strong>". $failedPretest ."</strong>", "error");
			ToolBox::conditional_header($goHere);
			exit;
		}
		
		
		
		// Check if username already exists
		$sql = 'SELECT `username`, `user_id` FROM users WHERE `username`="' . $clean['admin']['username'] . '"';
		$users = $db->fetch_array($sql);
		foreach ($users as $userData) {
			// should only result in one.
			if($clean['user_id'] != $userData['user_id']) {
				$errormsg = true;
				addAlert("Username Taken", 'Sorry, this username already exists. Please choose a different username.', "error");
				$goHere .= "item.php";
			}
		}

		if($errormsg == FALSE) {
			debugPrint($clean["admin"], "Data for the update/insert");
			// Save Page
			
			if($clean['admin']['is_active'] == 'on') {
				$clean['admin']['is_active'] = 1;
			}
			else {
				$clean['admin']['is_active'] = 0;
			}
			if(!empty($clean['user_id'])) {
				// Update
				if($clean['admin']['password'] == "NULL" || trim($clean['admin']['password']) == "") {
					// no password change intended.
					unset($clean['admin']['password']);
				}
				$user_id = intval($clean["user_id"]);
				$user->update($clean["admin"], $user_id);
				addAlert("Update Result", "Record has been updated");
			} else { // Insert
				$user_id = $user->insert($clean["admin"]);
				addAlert("Create Result", "Record #". $user_id ." has been added");
			}

			$sql = "DELETE FROM user_groups WHERE user_id = {$user_id}";
			$sth = $db->query($sql);

			// Groups
			if(isset($clean['groups']) && is_array($clean["groups"]) && count($clean['groups']) > 0) {
				foreach ($clean["groups"] as $group_id) {
					$sql = "INSERT INTO user_groups SET user_id = {$user_id}, group_id = {$group_id}	";
					$sth = $db->query($sql);
				}
			}

			$sql = "DELETE FROM acl WHERE user_id = {$user_id}";
			$db->query($sql);

			if(isset($clean['perm'])) {
				foreach ($clean['perm'] as $asset => $perm) {
					foreach ($perm as $asset_id => $values) {
						foreach ($values as $level => $value) {
							if('1' == $value) {
								$sql = "INSERT INTO acl SET user_id = {$user_id}, asset = 'dealers', permission = {$level}, asset_id = {$asset_id}";
								$db->query($sql);
							}
						}
					}
				}
			}
		}
		if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "Woulda redirected, but you appear to be debugging")) {
			crazedsanity\core\ToolBox::conditional_header($goHere);
		}
		exit;
	} 
	else {

		if(isset($clean['user_id'])) {
			$user_id = intval($clean['user_id']);
		} else {
			$user_id = 0;
		}
	}

	if(!isset($clean['admin'])) {
		$sql = "SELECT * FROM users WHERE user_id='{$user_id}'";
		$rs = $db->query_first($sql);
		unset($rs['password']);// security issue (this should really NOT get queried).
	} else {
		$rs = $clean['admin'];
	}

	$sql = "SELECT * FROM groups";
	$groups = $db->fetch_array_assoc($sql, 'group_id');

	if(!isset($clean['groups'])) {
		$sql = "SELECT * FROM user_groups WHERE user_id = '{$user_id}'";
		$selected_groups = $db->fetch_array($sql);
	} else {
		$selected_groups = $clean['groups'];
	}

	$selected_admin_groups = array();

	foreach ($selected_groups as $row) {
		$selected_admin_groups[$row['group_id']] = $row['group_id'];
//		$groups
		$groups[$row['group_id']]['selectedText'] = ' checked="checked" ';
	}
}

function inSet($findme, $searchstring) {
	$pos = strpos($searchstring, $findme);
	if($pos === false)
		return false;
	else
		return true;
}


$_TEMPLATE['PAGE_TITLE'] = 'Add/Edit Account';




if($access) { 
	
	$_tmpl = getTemplate('update/accounts/item.tmpl');
	$groupRow = $_tmpl->setBlockRow('group');
	
	if(is_array($rs) && count($rs) > 0) {
		debugPrint($rs, "user record");
		$_tmpl->addVarList($rs);
		if($rs['is_active'] == 1) {
			$_tmpl->addVar('isActive_checked', "checked");
		}
	}
	
	if(is_array($groups)) {
		$_tmpl->addVar($groupRow->name, $groupRow->renderRows($groups));
	}
	
	$_TEMPLATE['ADMIN_CONTENT'] = $_tmpl->render(true);
	
} else {
	echo $acl->accessDeniedMsg();
}

