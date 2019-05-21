<?php

use cms\adminMenu;
use crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt = 1;

$menuTmpl = getTemplate('update/menu.html');
$submenuItem = $menuTmpl->setBlockRow('submenuItem');
$submenu_ul = $menuTmpl->setBlockRow('submenu');
$menuRow = $menuTmpl->setBlockRow('menuItem');

$adminMenuObj = new adminMenu($db);
$allItems = $adminMenuObj->getAll();
debugPrint($allItems, "all menu items");

$rendered = "";
foreach($allItems as $code=>$record) {
	
	if($acl->hasAccess($record['code'])) {
		$hasChildren = false;
		$childRender = "";
		if(isset($record[adminMenu::CHILDINDEX])) {
			
			$_childRowsForRender = array();
			foreach($record[adminMenu::CHILDINDEX] as $cCode=>$cRecord) {
				$checkThis = $cRecord['asset_name'];
				if(!strlen($cRecord['asset_name'])) {
					$checkThis = $cRecord['code'];
				}
				if($acl->hasAccess($checkThis)) {
					$_childRowsForRender[$cCode] = $cRecord;
					$hasChildren = true;
				}
				else {
					debugPrint($cRecord, "no access to this child record");
				}
				unset($record[adminMenu::CHILDINDEX]);
			}
		}
		$menuRow->addVarList($record);

		if($hasChildren) {
			$childRender = $submenuItem->renderRows($_childRowsForRender);
			$submenu_ul->addVar($submenuItem->name, $childRender);
			$menuRow->addVar($submenu_ul->name, $submenu_ul->render());
		}
		$rendered .= $menuRow->render();
	}
	else {
		debugPrint($record, "skipping asset");
	}
	$menuRow->reset();
}

if(!isset($_TEMPLATE['ADMIN_URL'])) {
	$_TEMPLATE['ADMIN_URL'] = 'update';
}
$menuTmpl->addVar($menuRow->name, $rendered);

$_TEMPLATE['ADMIN_MENU'] = $menuTmpl->render();
