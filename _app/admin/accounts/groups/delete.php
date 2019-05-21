<?php

$user->superadmin = 1;
$user->restrict();

$access = false;
if($acl->access($_SESSION['MM_Username'], 'accounts', 0, DELETE)) {
	$access = true;
}


if(!empty($_POST) && isset($_POST['confirmation'])) {
	if($access) {
		if(isset($_POST['group_id']) && intval($_POST['group_id']) > 0 && $_POST['group_id'] === $_POST['confirmation']) {
			$delRes = $db->query("DELETE FROM groups WHERE group_id=" . intval($_POST['group_id']));
			addAlert("Group Deleted", "Record was deleted ({$delRes})", "notice");
			
			$aclDelRes = $db->query("DELETE FROM acl WHERE group_id=" . intval($_POST['group_id']));
			if($aclDelRes > 0) {
				addAlert("Group ACLs Deleted", "ACL records for this group were also deleted (". $aclDelRes .")", "notice");
			}
			else {
				addAlert("No Group ACLs", "There were no ACL records found, so none were deleted. (This is okay.)", "notice");
			}
			
			// CLEANUP!
			$cleanupRes = $db->query("DELETE FROM acl WHERE group_id NOT IN (SELECT group_id FROM groups)");
			if($cleanupRes > 0) {
				addAlert("CLEANUP", "Additionally, there were {$cleanupRes} invalid records deleted.", "status");
			}
		}
		else {
			addAlert("Missing Information", "Your request was missing information.  Please try again.", "error");
		}
	}
	else {
		addAlert("Permission Denied", "You don't have enough permission to do that.", "error");
	}
}
else {
	addAlert("Invalid Request", "You probably clicked an old link.  Please try again; if it continues to fail, try <code>&lsaquo;Shift&rsaquo; + reload</code>", "error");
}


crazedsanity\core\ToolBox::conditional_header('/update/accounts/groups/index.php');
exit;
