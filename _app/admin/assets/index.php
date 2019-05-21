<?php


use crazedsanity\core\ToolBox;
use cms\cms\core\assets;

//ToolBox::$debugPrintOpt = 1;

if(debugPrint("turning on display_errors")) {
	ini_set('display_errors', true);
}

$assetObj = new assets($db);


debugPrint($section, "section name");

$_TEMPLATE['keyName'] = 'asset_id';

$tmpl = getTemplate('update/assets/index.html');

if(!$acl->hasAdd($section)) {
	debugPrint($section, "no access to add stuff");
	$tmpl->setBlockRow('pageOptions');
}
if(!$acl->hasDelete($section)) {
	debugPrint($section, "no access to delete stuff");
	$tmpl->setBlockRow('rowOption_delete');
}
if(!$acl->hasEdit($section)) {
	debugPrint($section, "no access to edit stuff");
	$tmpl->setBlockRow('rowOption_edit');
	
}


$assetList = $assetObj->getAll();

// set some things for templating
foreach($assetList as $k=>$v) {
	if(intval($v['visible']) == 1) {
		$assetList[$k]['faIcon'] = 'check';
		$assetList[$k]['visibilityColor'] = 'green';
	}
}

debugPrint($assetList, "all assets");

$row = $tmpl->setBlockRow('row');

$tmpl->addVar($row->name, $row->renderRows($assetList));





echo $tmpl->render();


