<?php

use \cms\cms\core\gallery;

$asset = 'galleries';

if(isset($_POST["gallery"])) {

	// Save galleries
	if(!empty($_POST['gallery_id'])) {
		// Update
		$gallery_id = intval($clean["gallery_id"]);
		$updateRes = $db->update("galleries", $clean["gallery"], "gallery_id=" . $gallery_id);
		$location = "/update/galleries/item.php?gallery_id=" . $gallery_id;
		addMsg("Updated", "Gallery has been updated ({$updateRes})");
	} else {
		// Insert
		// Unset the hidden id
		unset($_POST["gallery_id"]);
		$gallery_id = $db->insert("galleries", $clean["gallery"]);
		$location = "/update/galleries/index.php?gallery_id=" . $gallery_id;
		addMsg("Gallery Added", "Gallery ID #{$gallery_id} was created.");
	}
	crazedsanity\core\ToolBox::conditional_header($location);
	exit;
} else {

	if(isset($clean['gallery_id'])) {
		$gallery_id = intval($clean['gallery_id']);
	} else {
		$gallery_id = 0;
	}

	if($gallery_id > 0) {
		$gObj = new gallery($db);
		$rs = $gObj->get($gallery_id);
	}
}

$_TEMPLATE['PAGE_TITLE'] = "Galleries";

$_tmpl = getTemplate('update/galleries/item.html');

$_tmpl->addVarList($rs);
echo $_tmpl->render();
		  