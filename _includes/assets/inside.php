<?php

include( 'homeandinside.php' );

use cms\cms\core\User;
use crazedsanity\core\ToolBox;
use cms\cms\core\menu;

//ToolBox::$debugPrintOpt = 1;
if(debugPrint("Errors displayed because debugging is on")) {
	ini_set('display_errors', true);
}


// FIRST try to load the parent's asset.
if(!empty($page['parent_id'])) {
	// attempt to get parent data.
	$parentPageInfo = $_pageObj->get($page['parent_id']);
	
	if(!empty($parentPageInfo['asset']) && file_exists(ASSETS_DIR . '/' . $parentPageInfo['asset']. '.php')) {
		try {
			include_once(ASSETS_DIR . '/' . $parentPageInfo['asset']. '.php');
		} catch (Exception $ex) {
			addAlert("Parent Asset Error", $ex->getMessage(), "error");
		}
	}
}


// Load submenus, kinda like top menus, dynamically, using "MENU_SIDE_" as a prefix.
$_menuObj = new menu($db);
$allMenus = $_menuObj->getAll_menus();
foreach($allMenus as $num=>$mData) {
	$id = $mData['menu_id'];
	$placeHolder = 'MENU_SIDE_'. strtoupper(trim(preg_replace('~ ~', '_', $mData['name'])));
	$_TEMPLATE[$placeHolder] = $base->getMenu($id, true, 1, $page['page_id'], $base->getThisPageMenuItemId($id,$page['page_id']));
}


$_TEMPLATE['USERNAME'] = '(Anonymous)';
$_TEMPLATE['LOGIN_WORD'] = 'LOGIN';
$_TEMPLATE['LOGIN_LINK'] = '/login.php';
if(isset($_SESSION['MM_Username'])) {
	$_TEMPLATE['USERNAME'] = $_SESSION['MM_Username'];
	$_TEMPLATE['LOGIN_WORD'] = 'LOGOUT';
	$_TEMPLATE['LOGIN_LINK'] = '/login.php?logout=1';
}

$loadAsset = true;

debugPrint($page);

$requiredGroupId = $_pageObj->getPageRequiredGroupId($page['page_id']);
debugPrint($requiredGroupId, "group id required by page");
if(intval($requiredGroupId) > 0) {
	$loadAsset = false;
	debugPrint($page, "authorization required...");
	debugPrint($_SERVER, "Server data");
	
	$_userObj = new User($db);
	
	if($_userObj->checkLogin()) {
		if($acl->isGroupMember($requiredGroupId)) {
			debugPrint("looks like you're good.");
			$loadAsset = true;
		}
		else {
			addAlert("Permission Denied", "You do not have permission to access this resource", "error");
			ToolBox::conditional_header('/');
			exit;
		}
	}
	else {
		addAlert("Authorization Required", "You must login to view this page", "notice");
		$loginTmpl = getTemplate('login.html')->setBlockRow('loginContent');
		$loginTmpl->addVar('loginFormAction', '/login.php');
		$loginTmpl->addVar('accesscheck', $_SERVER['REDIRECT_URL']);
		$_TEMPLATE['CONTENT'] = $loginTmpl->render();
//		$_TEMPLATE['CONTENT']
//		ToolBox::conditional_header('/login.php?accesscheck='. $_SERVER['REQUEST_URI']);
//		exit;
	}
	
	
//	exit;
}
else {
	debugPrint($page, "Authorization not required, carry on");
}


if($loadAsset && isset($asset) && !empty($asset)) {
	if(file_exists(ASSETS_DIR . '/' . $asset .'.php')) {
		include_once(ASSETS_DIR . '/' . $asset .'.php');
	}
}
