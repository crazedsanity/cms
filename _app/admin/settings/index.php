<?php


use crazedsanity\core\ToolBox;
use cms\cms\core\media;
//ToolBox::$debugPrintOpt=1;
if(debugPrint("turning on errors")) {
	ini_set('display_errors', true);
}


if($_POST) {
	
	// look for images to be removed.
	foreach($_POST as $k=>$v) {
		if(preg_match('/^remove/', $k) == 1 && preg_match('/image/', $k) == 1) {
			// walks like a duck (image)...
			
			$imgIndex = preg_replace('/^remove/', '', $k);
			if(isset($_POST['settings'][$imgIndex])) {
				// talks like a duck (image)...
				$mediaId = split('\.', $_POST['settings'][$imgIndex])[0];
				$_mediaObj = new media($db);
				$theRecord = $_mediaObj->get($mediaId);
				
				if(is_array($theRecord)) {
					// yep, it's a duck.  I mean... it's an image.
					debugPrint($theRecord, "image record");
					$delRes = $_mediaObj->delete($mediaId);
					addAlert("Image Deleted", "Deleted image for ". $imgIndex ." (". $delRes .")", "notice");
					
					$clean['settings'][$imgIndex] = '';
				}
			}
			
//			debugPrint($k, "found an image to be removed");
		}
	}
	
	debugPrint($_POST, "POSTed data");
	$status = $settings->save($clean['settings'], $clean['asset'], $clean['asset_id']);
	debugPrint($status, "result of calling save");
	$title = "Success";
	$type = "notice";
	if($status->error) {
		$title = "Error";
		$type = "error";
	}
	
	addAlert($title, $status->msg, $type);
	
	if(!debugPrint($status, "status")) {
		ToolBox::conditional_header('/update/settings');
	}
	exit;
}


$_TEMPLATE['PAGE_TITLE'] = '';



$settingsObj = new settings($db, $base);



$_tmpl = getTemplate('update/settings/index.tmpl');
$tab = $_tmpl->setBlockRow('tab');
$section = $_tmpl->setBlockRow('section');

$assets = $settingsObj->getAssets();
debugPrint($assets, "list of assets");

$_tmpl->addVar($tab->name, $tab->renderRows($assets));
//$_tmpl->addVar($section->name, $section->renderRows($assets));



//$renderedSections = $section->renderRows($assets);


$renderedSections = '';

foreach($assets as $k=>$v) {
	$section->reset();
	$section->addVarList($v);
	try {
	
		$section->addVar('listing', $settingsObj->listing($v['setting_category_id']));
	} catch (Exception $ex) {
		// no settings for that particular section... probably okay
		$section->addVar('listing', '<p>No settings for this category yet.</p>');
	}
	$renderedSections .= $section->render();
}

$_tmpl->addVar($section->name, $renderedSections);



//foreach($assets as $k=>$v) {
//	debugPrint($v, "asset info for k=(". $k .")");
//	
//}
//exit(__FILE__ ." - line #". __LINE__);

//$renderData = array();
//foreach($assets as $k=>$v) {
//	$v['listing'] = $settingsObj->listing($v['setting_category_id']);
//	$renderData[$k] = $v;
//}
//$_tmpl->addVar($section->name, $section->renderRows($renderData));



debugPrint($clean, "clean data");

echo $_tmpl->render(true);

