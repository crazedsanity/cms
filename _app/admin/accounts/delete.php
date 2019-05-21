<?php 


if(!empty($_POST)) {
	if(isset($_POST['user_id']) && intval($_POST['user_id']) > 0) {
		$userId = $_POST['user_id'];
		if ( $acl->access( $_SESSION['MM_Username'], 'accounts', $userId, DELETE ) ) {
			if(isset($_POST['confirmation']) && $_POST['confirmation'] === $userId) {
				
				$userDelRes = $user->delete(intval($userId));
				addAlert('User Record Deleted', "User record deleted (". $userDelRes .")", 'notice');

				$sql = "DELETE FROM user_groups WHERE user_id=" . intval( $userId );
				$groupDelRes = $db->query( $sql );
				if($groupDelRes > 0) {
					addAlert('UserGroup Record(s) Deleted', "The user_group record(s) were deleted (". $groupDelRes .")");
				}

				$sql = "DELETE FROM acl WHERE user_id=" . intval( $userId );
				$aclDelRes = $db->query( $sql );
				if($aclDelRes != 0) {
					addAlert('ACL Record(s) Deleted', "The acl record(s) were deleted (". $aclDelRes .")");
				}
			}
			else {
				addMsg("Failed", "The link may have been out-dated, please try again.", 'error');
			}
		}
		else {
			addMsg('Access Denied', "You do not have access to perform the requested action.", 'error');
		}
	}
	else {
		addMsg("Failed", "Not enough information to perform the requested action.", 'error');
	}
}
else {
	addAlert(
		"Invalid Request Type", 
		"You probably clicked an old link.  Please try again; if it continues to fail, try <code>&lsaquo;Shift&rsaquo; + reload</code>",
		'error'
	);
}

crazedsanity\core\ToolBox::conditional_header( '/update/accounts/index.php');
exit;
