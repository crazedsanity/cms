<?php

use \crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt = 1;
//ini_set('display_errors', true);

if(isset($clean['menu_item_id'])) {
	$menu_item_id = intval($clean['menu_item_id']);
} else {
	$menu_item_id = 0;
}


$_TEMPLATE['PAGE_TITLE'] = 'Menu - Edit';

if(isset($clean["menu_item"])) {
	// Save Page
	if($clean['menu_item']['link'] != '') {
		$clean['menu_item']['page_id'] = 0;
	}
	if($clean['menu_item']['sub_menu_id'] <= 0) {
		$clean['menu_item']['sub_menu_id'] = 'NULL';
	}
	$title = "Success";
	if(!empty($clean['menu_item_id'])) {
		// Update
		$page_id = intval($clean["page_id"]);
		$db->update("menu_items", $clean["menu_item"], "menu_item_id=" . $menu_item_id);
		$alert = "Record has been updated";
		$location = "/update/menu/item.php?menu_item_id=" . $menu_item_id;
	} else {
		// Insert
		// Unset the hidden id
		unset($clean["menu_item_id"]);
		$sql = "SELECT max(`sort`)+1 AS sort FROM menu_items";
		$sort = $db->query_first($sql);
		$clean['menu_item']['sort'] = $sort['sort'];

		$menu_item_id = $db->insert("menu_items", $clean["menu_item"]);
		$alert = "Record has been added";
	}
		$location = "/update/menu/index.php?menu_item_id=" . $menu_item_id;

	addMsg($title, $alert);
	ToolBox::conditional_header($location);
	exit;
} else {
	if(isset($clean['menu_item_id'])) {
		$menu_item_id = intval($clean['menu_item_id']);
		$sql = "SELECT * FROM menu_items WHERE menu_item_id='{$menu_item_id}'";
		$rs = $db->query_first($sql);
	} else {
		$menu_item_id = 0;
		$rs = array(
			'page_id'		=> 0,
			'parent_id'		=> 0,
			'sub_menu_id'	=> 0,
			'menu_id'		=> $_GET['menu_id'],
		);
	}
}

$_tmpl = getTemplate('update/menu/item.html');

$_tmpl->addVarList($rs);


// linked page options list
$pageOptionsList = '<option value="0">-- Site Root --</option>';
if(!isset($rs['page_id'])) {
	$rs['page_id'] = 0;
}
$pageObj = new cms\cms\core\page($db);
$pageOptionsList .= $pageObj->getPageOptionsList(null, $rs['page_id']);
$_tmpl->addVar('pageOptionsList', $pageOptionsList);


// Top menu options list
$topMenuOptionsList = '<option value="0">-- Menu Root --</option>';
if(!isset($rs['sub_menu_id'])) {
	$rs['sub_menu_id'] = 0;
}
$topMenuOptionsList .= $base->getTopMenuOptions($rs['sub_menu_id'], $rs['menu_id']);
$_tmpl->addVar('topMenuOptionsList', $topMenuOptionsList);


// parent menu options list
$parentMenuOptionsList = '<option value="0">-- Menu Root --</option>';
if(!isset($rs['parent_id'])) {
	$rs['parent_id'] = 0;
}
$parentMenuOptionsList .=  $base->getMenuOptions('', $rs['menu_id'], $rs['parent_id'], 0, $menu_item_id);
$_tmpl->addVar('parentMenuOptionsList', $parentMenuOptionsList);


echo $_tmpl->render();
	
