<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\notification;
//ToolBox::$debugPrintOpt = 1;

$location = '/update/notifications/item.php';
if(isset($_POST['notifications']['notification_id']) && is_numeric($_POST['notifications']['notification_id']) && $_POST['notifications']['notification_id'] > 0) {
	$theId = $_POST['notifications']['notification_id'];
	$type = 'update';
	$aclType = constant('EDIT');
}
else {
	$theId = null;
	$type = 'create';
	$aclType = constant('ADD');
}
if($_POST && $acl->access( $_SESSION['MM_Username'], 'notifications', $theId, $aclType ) && count($clean)) {
	$urlExtra = "";
	try {
		$_obj = new notification($db);
		if($type == 'update') {
			$result = $_obj->update($clean['notifications'], $theId);
			addAlert("Notification Updated", "Successfully updated notification #". $theId ." (". $result .")");
			$location .= "?notification_id=". $theId;
		}
		else {
			$theId = $_obj->create($clean['notifications']['title'], $clean['notifications']['body'], $clean['notifications']['notification_type_id']);
			addAlert("Notification Created", "Successfully created notification #". $theId);
			$location = dirname($location);
		}
	} catch (Exception $ex) {
		addAlert("Creation Failed", $ex->getMessage(), "fatal");
	}
}
else {
	addAlert("Access Denied", "You do not have access to create a notification", "error");
}

ToolBox::conditional_header($location);
exit;
