<?php

use crazedsanity\core\ToolBox;
use crazedsanity\messaging\MessageQueue;


if(!isset($render)) {
	$render = true;
}
/* For compatibility with nginx with older versions of PHP... */
if(!function_exists('getallheaders')) {

	function getallheaders() {
		if(!is_array($_SERVER)) {
			return array();
		}

		$headers = array();
		foreach ($_SERVER as $name => $value) {
			if(substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}

$mytime = time();

// Parse URL
$url = null;
if(isset($_GET['url'])) {
	$url = cleanURL(strtolower($_GET['url']));
	$urls = explode('/', trim($url, '/'));
} else {
	$urls = array();
}

// Escape urls for possible sql injection
//foreach ($urls as $i => $u) {
//	$urls[$i] = @mysql_escape_string($u);
//}

//\crazedsanity\core\ToolBox::$debugPrintOpt = 1;

$page = array();
$_pageObj = new \cms\page($db);
$isHomepage = false;
if(!empty($url)) {
	$testurls = $urls;

	$queryurl = '';
	foreach ($testurls as $i => $test) {

		if($i > 0) {
			array_pop($testurls);
		}

		$queryurl = '/' . implode('/', $testurls);

		if(!isset($page['page_id'])) {
			$queryurl .= '/'; //check for version with trailing slash
			$page = $_pageObj->getByUrl($queryurl);
		}
	}
	if(!(debugPrint($page, "Page data") && debugPrint($testurls, "Test URLs"))) {
		if(empty($page)) {
			header('Location: /');
			exit;
		}
	}
}
else {
	// default page
	$isHomepage = true;
	$page = $_pageObj->getHomepage();
}
debugPrint($url, "URL");
debugPrint($page, "Page data");


if(isset($page['redirect']) && strlen($page['redirect'])) {
	// found a redirect! only do the redirection if there's no debugging
	if(!debugPrint($page['redirect'], "Redirect found, but you're debugging")) {
		ToolBox::conditional_header($page['redirect']);
	}
	exit();
}

foreach($page as $k=>$v) {
	$templateIdx = 'PAGE_'. strtoupper($k);
	if(!isset($_TEMPLATE[$templateIdx])) {
		debugPrint($templateIdx, "MAIN: setting template index");
		$_TEMPLATE[$templateIdx] = $v;
	}
}

$_TEMPLATE['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

$urlBits = parse_url($_SERVER['REQUEST_URI']);
$_TEMPLATE['PAGE_URL'] = preg_replace('~/$~', '', $urlBits['path']);

$_TEMPLATE['ASSET_URL'] = preg_replace('~/$~', '', $page['url']);

// set some date-based variables.
$_TEMPLATE['YEAR'] = date('Y');
$_TEMPLATE['SELECTED_YEAR_'. date('Y')] = 'selected';	// matches '{SELECTED_YEAR_2016}' in 2016
$_TEMPLATE['SELECTED_MONTH_'. date('n')] = 'selected';	// matches '{SELECTED_MONTH_3}' in March
$_TEMPLATE['DAYNUM_CURRENT'] = date('j');				// matches '{DAYNUM_CURRENT_17}' for March 17th.


$myheaders = getallheaders();
$myuseragent = $_SERVER['HTTP_USER_AGENT'];

$myheaders = array('headers' => $myheaders);

if(isset($page['og_title'])) {
	$_TEMPLATE['OG_TITLE'] = $page['og_title'];
} else if(isset($page['title'])) {
	$_TEMPLATE['OG_TITLE'] = $page['title'];
} else {
	$_TEMPLATE['OG_TITLE'] = $settings->get('title', 'site');
}

if(isset($page['og_image_filename']) && $base->isOgImage($page['og_image_filename'])) {
	$_TEMPLATE['OG_IMAGE'] = 'http://' . $_SERVER['SERVER_NAME'] . '/data/upfiles/media/' . $page['og_image_filename'];
} else if($base->isOgImage($settings->get('og_image', 'site'))) {
	$_TEMPLATE['OG_IMAGE'] = 'http://' . $_SERVER['SERVER_NAME'] . '/data/upfiles/media/' . $settings->get('og_image', 'site');
}

if(isset($page['og_description'])) {
	$_TEMPLATE['OG_DESCRIPTION'] = $page['og_description'];
} else if(isset($page['description'])) {
	$_TEMPLATE['OG_DESCRIPTION'] = $page['description'];
} else {
	$_TEMPLATE['OG_DESCRIPTION'] = $settings->get('description', 'site');
}

$_TEMPLATE['OG_SITE_NAME'] = $settings->get('name', 'site');

$_TEMPLATE['OG_TYPE'] = '';
$_TEMPLATE['OG_URL'] = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

// Menus
if(!isset($page['page_id'])) {
	$page['page_id'] = null;
}


// Add menus dynamically (placeholders are "MENU_{name}", so a menu named "Top" would show in "MENU_TOP")
$_menuObj = new \cms\menu($db);
$allMenus = $_menuObj->getAll();
foreach($allMenus as $num=>$mData) {
	// *sigh*
	$id = $mData['menu_id'];
	$menuName = strtoupper(trim(preg_replace('~ ~', '_', $mData['name'])));
	$placeHolder = 'MENU_'. $menuName;
	$parseThis = $base->getMenu($id, TRUE, -1, $page['page_id']);
	$_TEMPLATE[$placeHolder] = $parseThis;
	debugPrint($placeHolder, "Menu placeholder");
	debugPrint(htmlentities($parseThis), "Data for menu ({$placeHolder})");
	
	// load a dynamic menu that shows sub-pages based on the current page.
	$dynamicPageMenu = 'PAGEMENU__'. $menuName;// so "MAIN" would turn into "PAGEMENU__MAIN'
	if(!empty($page['page_id']) && $isHomepage === false) {
		try {
//			ToolBox::$debugPrintOpt = 1;
			debugPrint($base->getThisPageMenuItemId($id,$page['page_id']), "page menu item id");
			debugPrint($id, "menu ID");
			debugPrint($page['page_id'], "page id");
			$rdpm = $base->getMenu($id, TRUE, 1, $page['page_id'], $base->getThisPageMenuItemId($id,$page['page_id']));
			debugPrint(htmlentities($rdpm), "Rendered page menu");
			ToolBox::$debugPrintOpt = 0;
			$_TEMPLATE[$dynamicPageMenu] = $rdpm;
		}
		catch(Exception $ex) {
			// nothing to see here, move along.
		}
	}
}
debugPrint($allMenus, "All menus");



if(!isset($page['title'])) {
	$page['title'] = null;
}
if(!isset($page['parent_id'])) {
	$page['parent_id'] = null;
}

if(!isset($_TEMPLATE['BODYCLASS'])) {
	$_TEMPLATE['BODYCLASS'] = "";
}
$_TEMPLATE['BODYCLASS'] .= ' landing ';
$_TEMPLATE['FEATURE_TITLE'] = $page['title'];
$_TEMPLATE['RIGHT_NAV'] = '';


if(!empty($page['template'])) {
	try {
		$tryThis = $page['template'] .'.html';
		$test = getTemplate($page['template'] .'.html');
		$templatefile = $tryThis;
		
	} catch (Exception $ex) {
//		debugPrint($ex->getMessage(), "Unable to use specified template");
		addAlert("Template Error", $ex->getMessage(), "error");
	}
//	$templatefile = 
}





debugPrint($templatefile, "Template file (ACTUALLY) used");

debugPrint($page, "Page information");
$pageTmpl = getTemplate($templatefile, 'BASE_CONTENT');







// Page Title
$_TEMPLATE['SITE_TITLE'] = $settings->get('divide', 'site') . $settings->get('title', 'site');
if(!empty($page['title'])) {
	$_TEMPLATE['TITLE'] = $page['title'] . $_TEMPLATE['SITE_TITLE'];
} else {
	$_TEMPLATE['TITLE'] = $settings->get('title', 'site');
}

$_TEMPLATE['TITLE'] = strip_tags($_TEMPLATE['TITLE']);

$_TEMPLATE['TIME'] = time();

if(debugPrint("Errors displayed because debugging is on")) {
	ini_set('display_errors', true);
}

$templatesOnlyForBase = array();
if(!empty($page['page_id']) && $isHomepage === false) { //////////// INSIDE PAGES
	debugPrint($page, "Page info");
	
	$asset = $page['asset'];
	$page_id = $page['page_id'];
	$parent_id = $page['parent_id'];
	
	if(preg_match('~^/do/~', $url) == 1 || preg_match('~^/stay/~', $url) == 1 || preg_match('~^/eat~', $url) == 1) {
		$templatefile = 'stay.html';
		debugPrint($url, "matched!");
	}


	try {
		$myCrumbs = $_pageObj->getBreadcrumbs($page['page_id']);
		$crumbTmpl = getTemplate('bits/breadcrumbs.html');
		$_crumbRow = $crumbTmpl->setBlockRow('crumb');
		$crumbTmpl->addVar($_crumbRow->name, $_crumbRow->renderRows($myCrumbs));
		$_TEMPLATE['BREADCRUMB'] = $crumbTmpl->render();
	}
	catch(Exception $ex) {
		addAlert("Crumb Error", "Error displaying breadcrumbs: ". $ex->getMessage(), "error");
	}
	
	$_TEMPLATE['KEYWORDS'] = $page['keywords'];
	$_TEMPLATE['DESCRIPTION'] = $page['description'];
	$_TEMPLATE['HEADLINE'] = $page['title'];
	$_TEMPLATE['CONTENT'] .= $page['body'];
	$_TEMPLATE['BODYCLASS'] .= ' inside ';
	$_TEMPLATE['ASSETCLASS'] = $asset;
	$_TEMPLATE['BODY_ID'] = 'inside';
	
	


	
	
	
	$sql = <<<SQL
					SELECT m.filename, m.media_id 
					FROM media m
					WHERE m.media_id ='{$page['media_id']}'
SQL;
	$rspageimg = $db->query_first($sql);
		
	if (!empty($rspageimg)) {
		
		$_TEMPLATE['BGIMG7'] = '.bgimg-7 {background-image: url("/data/upfiles/media/'.$rspageimg['filename'].'") !important;}';
	}
	
	
	
	

//ToolBox::$debugPrintOpt = 1;
	try {
		include('assets/inside.php');
	}
	catch(Exception $ex) {
		if(!debugPrint($ex->getMessage(), "error encountered, but you're debugging")) {
			$logAsset = $page['asset'];
			if(empty($logAsset)) {
				$asset = 'inside.php';
			}
			addAlert("Unexpected Error", "An error occurred while trying to load a page or asset (". $page['asset'] ."): <br>". $ex->getMessage(), "fatal");
			ToolBox::conditional_header('/');
		}
		debugPrint($ex, "Full backtrace");
		exit;
	}
	
	if(count(explode('.', $templatefile)) == 1) {
		$templatefile .= ".html";
	}
	$pageTmpl = getTemplate($templatefile, 'BASE_CONTENT');
	
	$lookForBlockRows = array('HEAD_EXTRA', 'FOOT_EXTRA');
	if(isset($assetTmpl) && get_class($assetTmpl) == get_class($pageTmpl)) {
		try {
			$blockRowList = $assetTmpl->get_block_row_defs();
		}
		catch(Exception $ex) {
			$blockRowList = array();
		}
		foreach($lookForBlockRows as $blockName) {
			if(in_array($blockName, $blockRowList)) {
//				$_TEMPLATE[$blockName] = $assetTmpl->setBlockRow($blockName);
				$templatesOnlyForBase[$blockName] = $assetTmpl->setBlockRow($blockName);
			}
		}
		
		$_TEMPLATE['CONTENT'] .= $assetTmpl->render(true);
	}
	
	
}
else {  //////////// HOME PAGE
	$_TEMPLATE['BREADCRUMB'] = $base->getBreadcrumbs($page['page_id'], $page['parent_id']);
	$_TEMPLATE['KEYWORDS'] = $page['keywords'];
	$_TEMPLATE['DESCRIPTION'] = $page['description'];
	$_TEMPLATE['HEADLINE'] = $page['title'];
	$_TEMPLATE['CONTENT'] = $page['body'];
	$_TEMPLATE['BODY_ID'] = 'home_one';
	if(!isset($_TEMPLATE['BODYCLASS'])) {
		$_TEMPLATE['BODYCLASS'] = "";
	}
	$_TEMPLATE['BODYCLASS'] .= ' home ';
	
	$pageTmpl = getTemplate($templatefile, 'BASE_CONTENT');
	include('assets/home.php');
	
	
	
	
	$blockRowList = $pageTmpl->get_block_row_defs();
	debugPrint($blockRowList, "block row list from homepage template");
	if(in_array('HEAD_EXTRA', $blockRowList)) {
		$templatesOnlyForBase['HEAD_EXTRA'] = $pageTmpl->setBlockRow('HEAD_EXTRA');
		$pageTmpl->addVar('HEAD_EXTRA', '');
	}
	if(in_array('FOOT_EXTRA', $blockRowList)) {
		$templatesOnlyForBase['FOOT_EXTRA'] = $pageTmpl->setBlockRow('FOOT_EXTRA');
		$pageTmpl->addVar('FOOT_EXTRA', '');
		debugPrint(array_keys($_TEMPLATE), "SET FOOT_EXTRA!!!!");
	}
	else {
		debugPrint(array_keys($_TEMPLATE), "missing FOOT_EXTRA (where are you?)");
	}
}

// any special columns in the page record can be set into templates here.
foreach($page as $k=>$v) {
	$_TEMPLATE['PAGE__'. strtoupper($k)] = $v;
}



// Allow the content of any page to get put into a placeholder.
foreach($_pageObj->getAll() as $id=>$data) {
	$_TEMPLATE['PAGECONTENT_'. $id] = $data['body'];
	$titleBasedPlaceholder = 'PAGECONTENT_'. cms\page::makeCleanTitle($data['url']);
	$_TEMPLATE[$titleBasedPlaceholder] = $data['body'];
}

// Page description
if(empty($_TEMPLATE['DESCRIPTION'])) {
	$_TEMPLATE['DESCRIPTION'] = $settings->get('description', 'site');
}

// Page Keywords
$sitekeywords = $settings->get('keywords', 'site');
if(!empty($_TEMPLATE['KEYWORDS']) && !empty($sitekeywords)) {
	// append site keywords to page keykwords
	$_TEMPLATE['KEYWORDS'] .= ',' . $sitekeywords;
} else if($sitekeywords && empty($_TEMPLATE['KEYWORDS'])) {
	// set keywords to be site only
	$_TEMPLATE['KEYWORDS'] = $sitekeywords;
} else if(empty($_TEMPLATE['KEYWORDS'])) {
	// there are no keywords
	$_TEMPLATE['KEYWORDS'] = '';
}




// Load template file and fill the template tags
$_mainTmpl = $pageTmpl;
$_mainTmpl->addVarList($_TEMPLATE);
$_mainTmpl->addVarList($templatesOnlyForBase);


// handle alerts.
$que = new MessageQueue(true);

// if there's a message, create it and then clear the queue.
if($que->getCount() > 0) {
	debugPrint($que, "There are messages");
	$alertTmpl = getTemplate('bits/alert.html');
	$_TEMPLATE['ALERT'] = $alertTmpl->renderRows($que->getAll());
	$pageTmpl->addVar('ALERT', $_TEMPLATE['ALERT']);
	$_mainTmpl->addVar('ALERT', $_TEMPLATE['ALERT']);
	
	// only clear the queue if the alerts will actually appear somewhere.
	$varDefs = $_mainTmpl->getVarDefinitions();
	if(isset($varDefs['ALERT']) && $varDefs['ALERT'] > 0) {
		debugPrint($que->getCount(), "Queue will be cleared, message count");
		$que->clear();
	}
	else {
		debugPrint($varDefs, "no place to show alerts, queue left untouched");
	}
}


if($render == true) {
	echo $_mainTmpl->render(true);
}

