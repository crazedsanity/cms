<?php

use cms\cms\core\media;
use cms\cms\core\gallery;
use cms\cms\core\galleryPhotos;

$user->superadmin = 1;
$user->restrict();

//ini_set('display_errors', true);
//\crazedsanity\core\ToolBox::$debugPrintOpt = 1;

$asset = 'galleries';
if(isset($clean['gallery_id'])) {
	$gallery_id = intval($clean['gallery_id']);
} else if(isset($clean['gallery_photo']['gallery_id'])) {
	$gallery_id = intval($clean['gallery_photo']['gallery_id']);
} else {
	$gallery_id = 0;
}
if(isset($clean['gallery_photo_id'])) {
	$gallery_photo_id = intval($clean['gallery_photo_id']);
} else if(isset($clean['gallery_photo']['gallery_photo_id'])) {
	$gallery_photo_id = intval($clean['gallery_photo']['gallery_photo_id']);
} else {
	$gallery_photo_id = 0;
}

$gObj = new gallery($db);
$pObj = new galleryPhotos($db);


if(!empty($_POST)) {
	if(isset($_POST["gallery_photo"])) {
		debugPrint($_POST, "POSTed data");
		
		$useMediaFolderId = $pObj->getMediaFolderId();

		$location = "/update/galleries/";
		$mediaObj = new media($db);
		$error = null;
		
		$insertData = array(
			'media_folder_id'	=> $useMediaFolderId,
		);
		
		// Save galleries
		if(!empty($_POST['gallery_photo_id'])) {
			// Update
			$sql = "SELECT media_id FROM gallery_photos WHERE gallery_photo_id = '{$gallery_photo_id}'";
			$currentinfo = $db->query_first($sql);
			debugPrint($currentinfo, "existing gallery photo data");
			$media_id = $currentinfo['media_id'];


			if(!$_FILES['image']['error'] && $_FILES['image']) {
				$insertData['media_id'] = $media_id;
				if(!empty($_POST['gallery_photo']['name'])) {
					$insertData['display_filename'] = $_POST['gallery_photo']['name'];
				}
				debugPrint($insertData, "data for new photo");
				
				try {
					$uploadRes = $mediaObj->upload('image', null, $insertData, 'update');
					$clean['gallery_photo']['media_id'] = $uploadRes;
				}
				catch(Exception $ex) {
					$error = $ex->getMessage();
				}
			} else if($clean['removeimage']) {
				$clean['gallery_photo']['media_id'] .= '';
				if(file_exists($image_uploads_dir . $currentimage) && $currentimage) {
					unlink($image_uploads_dir . $currentimage);
				}
			}
			if(!$error) {
				$updateRes = $db->update("gallery_photos", $clean["gallery_photo"], "gallery_photo_id=" . $gallery_photo_id);
//				$location = "/update/galleries/photos/item.php?gallery_id=" . $gallery_id . "&gallery_photo_id=" . $gallery_photo_id;
				addAlert("Photo Updated", "The photo was updated ({$updateRes})");
			}
			else {
				addAlert("Error Encountered", $error, "error");
			}
		} else {
			// Insert
			if(!$_FILES['image']['error'] && $_FILES['image']) {
//				$media = $base->insertMedia('image', 0, 12, 0);
				if(!empty($_POST['gallery_photo']['name'])) {
					$insertData['display_filename'] = $_POST['gallery_photo']['name'];
				}
				debugPrint($insertData, "data for new photo");
//				exit(__FILE__ ." - line #". __LINE__);
				try {
					$mediaId = $mediaObj->upload('image', null, $insertData, 'insert');
					$info = $mediaObj->get($mediaId);
					$clean['gallery_photo']['name'] = $info['display_filename'];
					$clean['gallery_photo']['media_id'] = $mediaId;
				} 
				catch(Exception $ex) {
					$error = $ex->getMessage();
				}
			}
			if(!$error) {
				// Unset the hidden id
				unset($_POST["gallery_photo_id"]);
				$insertRes = $db->insert("gallery_photos", $clean["gallery_photo"]);
				$location = "/update/galleries/";
				addAlert("Photo Added", "The photo has been added ({$insertRes})");
			}
			else {
				addAlert("Error Encountered", $error, "error");
			}
		}
		
		debugPrint($_SESSION['messages'], "alerts");
		if(!debugPrint("<a href='{$location}'>{$location}</a>", "You're debugging, so no redirection, here's a link instead")) {
			crazedsanity\core\ToolBox::conditional_header($location);
		}
		exit;
	} 
}



$useName = '';
if($gallery_photo_id) {
	$pObj->galleryId = $gallery_photo_id;
	$rs = $pObj->get($gallery_photo_id);
	$useName = $rs['name'];
} else if($gallery_id) {
	$rs = $gObj->get($gallery_id);
}
$sql = <<<SQL
		SELECT gallery_id, name
		FROM galleries
		ORDER BY name ASC	
SQL;
$tempgalleries = $db->fetch_array($sql);
$galleries = array();

foreach ($tempgalleries as $gallery) {

	$galleries[$gallery['gallery_id']] = $gallery['name'];
}
//	}

debugPrint($rs, "record data");

$_TEMPLATE['PAGE_TITLE'] = "Galleries";

$_tmpl = getTemplate('update/galleries/photos/item.html');

$selectedGalleryId = null;

if(isset($rs['gallery_id']) && intval($rs['gallery_id']) > 0) {
	$selectedGalleryId = $rs['gallery_id'];
}

$_tmpl->addVarList($rs);
$_tmpl->addVar('photo_name', $useName);
$_tmpl->addVar('galleriesOptionList', crazedsanity\core\ToolBox::array_as_option_list($galleries, $selectedGalleryId));

echo $_tmpl->render();
