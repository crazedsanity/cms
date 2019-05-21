<?php

use crazedsanity\core\ToolBox;

// Save Media Sort
if(isset($clean["sort"])) {
	$ids = explode(",", $clean["sort"]);
	$num = 1;
	foreach ($ids as $node) {
		unset($sort);
		if($node > 0) {
			$sort['sort'] = $num;
			$db->update("galleries", $sort, "gallery_id=" . $node);
			$num++;
		}
	}
	addMsg("Success", "Records were sorted successfully ({$num})");
	ToolBox::conditional_header('/update/galleries');
	exit;
}
if(!isset($category)) {
	$category = '';
}
if(isset($clean['gallery_id'])) {
	$gallery_id = $clean['gallery_id'];
} else {
	$gallery_id = 0;
}

$sql = "SELECT * FROM galleries ORDER BY sort ASC";

$pages = $db->fetch_array($sql);

$_tmpl = getTemplate('update/galleries/sort.html');
$rowTmpl = $_tmpl->setBlockRow('row');
$_tmpl->addVar('category');
$_tmpl->addVar($rowTmpl->name, $rowTmpl->renderRows($pages));

echo $_tmpl->render();