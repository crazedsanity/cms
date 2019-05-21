<?php

// TODO: use POST exclusively (because "GET" requests should ONLY retrieve records, NEVER create, update, or delete them).

// set some reasonable defaults.
$type = null;
$goHere = "/update/media/";

//ini_set('display_errors', true);
//\crazedsanity\core\ToolBox::$debugPrintOpt = 1;

// determine the type (folder or media)
if(isset($_POST['type'])) {
	$type = $_POST['type'];
}

debugPrint($_POST, "POST data");

if(!is_null($type)) {
	try {
		switch($type) {

			// TODO: delete constraints (or change database so deletes cascade)
			case 'folder':
				$id = null;
				if(isset($_POST['media_folder_id']) && intval($_POST['media_folder_id']) > 0) {
					$id = intval($_POST['media_folder_id']);
				}
				elseif(isset($_GET['media_folder_id']) && intval($_GET['media_folder_id']) > 0) {
					$id = intval($_GET['media_folder_id']);
				}

				if(!is_null($id)) {
					$obj = new cms\mediaFolder($db);
					$res = $obj->delete($id);

					addMsg("Folder Deleted", "The folder appears to have been deleted successfully, result was (". $res .")");
				}
				else {
					addMsg("Invalid Folder ID", "No valid ID was found in the request.  Please try again.", "error");
				}
				break;

			// TODO: delete all tags (or change database so deletes cascade)
			case 'media':
				$id = null;
				if(isset($_POST['media_id']) && intval($_POST['media_id']) > 0) {
					$id = intval($_POST['media_id']);
				}
				elseif(isset($_GET['media_id']) && intval($_GET['media_id']) > 0) {
					$id = intval($_GET['media_id']);
				}

				if(!is_null($id)) {
					$obj = new cms\media($db);
					$result = $obj->delete($id);
					addMsg("Media Deleted", "Media ID #". $id ." was deleted (". $result .")");
				}
				else {
					addMsg("Invalid Media ID", "No valid media ID was found in the request.  Please try again.", "error");
				}
				break;

			default:
				addAlert("Invalid Type", "Your request had an invalid type.  Please try again.", "error");
		}
	}
	catch(Exception $ex) {
		addAlert("Database Error", "Got an error when trying to delete that record: ". $ex->getMessage(), "fatal");
	}
}
else {
	addAlert("Missing Type", "Your request was missing a type, so no action could be performed.", "error");
}

// Redirect 'em to get rid of POST vars.
if(!debugPrint("<a href='{$goHere}'>$goHere</a>", "Would have redirected, but you're debugging")) {
	crazedsanity\core\ToolBox::conditional_header($goHere);
}
exit;
