<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\notification;

//ToolBox::$debugPrintOpt = 1;

$location = '/update/notifications/';

debugPrint($_POST, "POSTed data");

if(isset($_POST['notification_id']) && is_numeric($_POST['notification_id']) && $_POST['notification_id'] > 0) {
	$theId = $_POST['notification_id'];
	
	if($acl->access( $_SESSION['MM_Username'], 'notifications', $theId, DELETE )) {
		try {
			$_obj = new notification($db);

			$result = $_obj->delete($theId);

			if($result == 1) {
				$urlBits['alerttype'] = 'success';
				addAlert("Delete Successful", "The notification was deleted successfully");
			}
			else {
				addAlert("Delete Failed", "Delete of nofification #{$theId} failed, DETAILS: {$result}", "error");
			}

		} catch (Exception $ex) {
			addAlert("Error Encountered", $ex->getMessage(), "fatal");
		}
	}
	else {
		addAlert("Access Denied", "You do not have permission to perform the requested operation", "error");
	}
	
}
else {
	addAlert("Not Enough Information", "There was not enough information to complete the requested operation", "error");
}


if(!debugPrint("<a href='{$location}'>{$location}</a>", "No redirection (you're debugging), here's the URL")) {
	ToolBox::conditional_header($location);
}
exit;
