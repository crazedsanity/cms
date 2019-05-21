<?php

//crazedsanity\core\ToolBox::$debugPrintOpt = 1;
//ini_set('display_errors', true);

$asset = 'galleries';


$galObj = new \cms\cms\core\gallery($db);
$galleries = $galObj->getAll();

$pObj = new cms\cms\core\galleryPhotos($db);
$_tmpl = getTemplate('update/galleries/index.html');

if(!$acl->hasAdd($section)) {
	$_tmpl->setBlockRow('hasAdd');
}
$photoRow = $_tmpl->setBlockRow('photoRow');
$galleryRow = $_tmpl->setBlockRow('galleryRow');


debugPrint($galleries, "Gallery records");


$curGalId = null;
$parseRows = array();
$rendered = '';

$arranged = $galleries;

foreach($galleries as $gallery) {
	$pObj->galleryId = $gallery['gallery_id'];
	$thePhotos = $pObj->getAll();
	debugPrint($thePhotos, "all photos");
	$galId = $gallery['gallery_id'];
	if(!isset($arranged[$galId])) {
		$arranged[$galId] = $gallery;
	}
	$photoId = $gallery['gallery_photo_id'];
	$arranged[$galId]['_items_'] = $thePhotos;
}

debugPrint($arranged, "arranged data");


foreach($arranged as $gId=>$data) {
	if(isset($data['_items_'])) {
		$items = $data['_items_'];
		unset($data['_items_']);
		$galleryRow->addVar($photoRow->name, $photoRow->renderRows($items));
	}
	$galleryRow->addvarList($data);
	
	$rendered .= $galleryRow->render();
	$photoRow->reset();
	$galleryRow->reset();
}

debugPrint(htmlentities($rendered), "rendered page");
$_tmpl->addVar($galleryRow->name, $rendered);






echo $_tmpl->render();