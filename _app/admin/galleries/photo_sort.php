<?php 

use crazedsanity\core\ToolBox;
ini_set('display_errors', true);
ToolBox::$debugPrintOpt = 1;

// Save Media Sort
if (isset($clean["sort"])) {
	$ids = explode(",", $clean["sort"]);

	
	$num = 1;
	foreach($ids as $node) {
		unset($sort);
		if($node > 0) {
			$sort['sort'] = $num;
			$db->update("gallery_photos",  $sort, "gallery_photo_id=".$node);
			$num++;
		}
	}
	$alert = "Sort updated.";
	addMsg("Success", "Sort order updated successfully ({$num})");
	ToolBox::conditional_header('/update/galleries');
	exit;
}
if(!isset($category)){
	$category='';
}
if(isset($clean['gallery_photo_id'])){
	$gallery_id = $clean['gallery_photo_id'];
}
else{
	$gallery_photo_id=0;	
}

$sql="SELECT gp.name as photoname, gp.gallery_id, gp.sort, gp.gallery_photo_id, g.name, g.sort FROM gallery_photos gp LEFT JOIN galleries g on g.gallery_id = gp.gallery_id ORDER BY gp.sort ASC, g.sort ASC";

$pages = $db->fetch_array($sql);

$_TEMPLATE['PAGE_TITLE'] = ucfirst($category) ." Gallery Photos";

$_tmpl = getTemplate('update/galleries/photo_sort.html');


//$_tmpl->addVarList($pages);
$_tmpl->addVar('category', ucfirst($category));
$row = $_tmpl->setBlockRow('row');
$section = $_tmpl->setBlockRow('section');

//debugPrint($pages, "photo data");

$sorted = array();
foreach($pages as $v) {
	$idx = $v['gallery_id'];
	if(!isset($sorted[$idx])) {
		$sorted[$idx] = array();
	}
	$sorted[$idx][] = $v;
}

debugPrint($sorted, "sorted data");

$rendered = '';
foreach($sorted as $gId=>$pData) {
	$section->addVarList($pData);
	debugPrint(htmlentities($row->renderRows($pData)), "rendered rows");
	$section->addVar($row->name, $row->renderRows($pData));
	$rendered .= $section->render();
}


$_tmpl->addVar($section->name, $section->render());


echo $_tmpl->render();

