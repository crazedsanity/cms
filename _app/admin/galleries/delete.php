<?php 


use crazedsanity\core\ToolBox;
use cms\cms\core\galleryPhotos;
use \cms\cms\core\gallery;
//ToolBox::$debugPrintOpt = 1;


if(!empty($_POST['type']) && (intval($_POST['gallery_id']) > 0 || intval($_POST['gallery_photo_id']) > 0)) {
	$hasAccess = false;
	if(isset($_POST['gallery_id'])) {
		$hasAccess = $acl->access($_SESSION['MM_Username'], $asset, intval($_POST['gallery_id']), DELETE);
	}
	elseif(isset($_POST['gallery_photo_id'])) {
		$hasAccess = $acl->access($_SESSION['MM_Username'], $asset, intval($_POST['gallery_photo_id']), DELETE);
	}

	$asset = 'galleries';
	$type = $_POST['type'];
	
	debugPrint($_POST, "POSTed data");
	
	if($hasAccess) {
		$gpObj = new galleryPhotos($db, intval($_POST['gallery_id']));
		switch($type) {
			case 'gallery':
				$recs = $gpObj->getAll();
				debugPrint($recs, "existing records");
				if(is_array($recs) && count($recs) == 0) {
					$gObj = new gallery($db);
					$delRes = $gObj->delete($_POST['gallery_id']);
					addAlert("Delete Successful", "The gallery was deleted (". $delRes .")", "notice");
					
				}
				else {
					addAlert("Unable to Delete", "There are still photos in this gallery.  Please delete them first, then delete the gallery.", "error");
				}
				break;

			case 'gallery_photo':
				$delRes = $gpObj->delete($clean['gallery_photo_id']);
//					$sql = "DELETE FROM gallery_photos WHERE gallery_photo_id=".$clean['gallery_photo_id'];
//					$delRes = $db->query($sql);
					addAlert("Delete Successful", "The record was deleted successfully ({$delRes})");
				break;

			default:
				addAlert("Unable to Delete", "Your request contained an invalid type (". $type .")", "fatal");
		}
	}
	else {
		addAlert("Not Enough Information", "Could not process your request, there was not enough information supplied", "error");
	}
}
else {
	addAlert("Access Denied", "Insufficient permissions to delete the requested resource", "error");
}

$location = '/update/galleries/';
if(!debugPrint("<a href='{$location}'>{$location}</a>", "You're debugging, so here's a link instead")) {
	ToolBox::conditional_header($location);
}
exit;
