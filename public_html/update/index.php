<?php 


//error_reporting(-1);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

require_once( __DIR__ ."/../../_app/core.php");
//error_reporting(E_ALL);
//ini_set('display_errors', true);

// Avoid caching stuff!
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

use crazedsanity\messaging\Message;
use crazedsanity\messaging\MessageQueue;
use crazedsanity\core\ToolBox;
use cms\cms\core\media;
//ToolBox::$debugPrintOpt = 1;

//start buffering, so things calling header() won't get errors (such as for redirects)
$user->superadmin = 1;
$user->restrict();

if(!$user->hasCmsAccess()) {
	// Check for the race condition where the user no longer exists...
	$userInfo = $user->lookupUser($_SESSION['MM_UserID'], 'user_id');
	if(is_null($userInfo)) {
		// user doesn't exist.
		$user->logout();
		addAlert("Invalid User", "The user you tried to authenticate as no longer exists.", "error");
		ToolBox::conditional_header('/update/login.php?error=invalid-user');
		exit;
	}
	
	// TODO: the call to User::restrict() is technically supposed to do something like this...
	if(!debugPrint("permission denied")) {
		addAlert("Permission Denied", "You do not have permission to access this part of the site.", "error");
		ToolBox::conditional_header('/'); // TODO: this should NOT be hard-coded, possibly non-existent page URL.
	}
	exit;
}

// Set the main template, add a couple of variables.
$_adminTmpl = getTemplate('update/base.tmpl');
//$_adminTmpl->addVar('TIME', time());

$_TEMPLATE['TIME'] = time();
$_adminTmpl->addVar('TITLE_DIVIDE', TITLE.DIVIDE);
$pageUrl = dirname($_SERVER['SCRIPT_NAME']);
$_adminTmpl->addVar('TITLE_DIVIDE', TITLE.DIVIDE);
$adminUrl = $pageUrl;
$_TEMPLATE['ADMIN_URL'] = $pageUrl;
$_TEMPLATE['USERNAME'] = $_SESSION['MM_Username'];



$defConstants = get_defined_constants(true);
if(isset($defConstants['user']) && count($defConstants['user']) > 0) {
	foreach($defConstants['user'] as $k=>$v) {
		$_TEMPLATE[$k] = $v;
	}
}


// Build the menu dynamically
require_once(BASE.'/_app/admin/_includes/menu.php');


function addMsg($title, $body, $type=Message::DEFAULT_TYPE) {
	trigger_error("The function ". __FUNCTION__ ." is deprecated. Stop using it.", E_USER_NOTICE);
	return addAlert($title, $body, $type);
}

$useThis = '';

// update the page URL, based on the apache redirect (what appears to be "/update/news/item.php?id=1" is really "/update/index.php?u=news/item.php&id=1)"
if(isset($_SERVER['REDIRECT_URL'])) {
	debugPrint($_GET, "GET Vars");
	
	//include based on redirected url.
	$originalUrl = $_SERVER['REDIRECT_URL'];
	
	$useThis = preg_replace('~^/update~', '', $originalUrl);
	
	// TODO: handle sub-sections better... 
	$section = preg_replace('~^/{0,}~', '', $useThis);
	$sectionBits = explode('/', $section);
	if(count($sectionBits)) {
		$firstBit = array_shift($sectionBits);
		if(!empty($firstBit)) {
			$section = $firstBit;
		}
	}
	
	if(preg_match('~/$~', $originalUrl)) {
		$useThis .= 'index.php';
	}
	elseif(!preg_match('~/$~', $originalUrl) && file_exists(BASE .'/_app/admin/'. $useThis .'/index.php')) {
		$useThis .= '/index.php';
	}
	$pageUrl = $originalUrl;
	
//	ToolBox::$debugPrintOpt = 1;
	$sectionUrl = $originalUrl;
	if(preg_match('~/$~', $sectionUrl)) {
		$sectionUrl = preg_replace('~/$~', '', $sectionUrl);
		debugPrint(__LINE__, "MARK! line #");
	}
	elseif(preg_match('~/(.+)\.php~', $sectionUrl)) {
		$sectionUrl = dirname($sectionUrl);
		debugPrint(__LINE__, "MARK! line #");
	}
	elseif(preg_match("~^{$section}$~", $_GET['u'])) {
		/*
		 * FIX URLS THAT LACK A TRAILING SLASH!!!
		 * EXAMPLE:: "/update/pages" instead of "/update/pages/"
		 * 
		 * The missing trailing slash creates problems when using relative URLs 
		 * like href="item.php": it will unexpected go to "/update/item.php" 
		 * instead of "/update/pages/item.php"
		 */
		debugPrint($_GET, "URL from apache");
		
		$goHere = "/update/{$section}/";
		
		if(!empty($_POST)) {
			// Uh-oh. Potentially redirecting before the script had a chance to deal with POST.  Yikes.
			if(!debugPrint(array_keys($_POST), "Your request had POST vars! THEY WILL BE LOST!")) {
				addAlert("Redirection Problem", "There were POST vars in the request, but were lost due to an invalid URL.  You may wish to report this. <pre>". var_dump($_POST,1) ."</pre>", "error");
			}
			debugPrint($_POST, "full POST output follows");
		}
		else {
			if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "woulda redirected, but you're debugging")) {
				ToolBox::conditional_header($goHere);
			}
			exit;
		}
	}
	debugPrint($sectionUrl, "section URL");
	
//	$pageUrl = $originalUrl;
	$_adminTmpl->addVar('PAGE_URL', $pageUrl);
	$_TEMPLATE['PAGE_URL'] = $pageUrl;
	
	$_adminTmpl->addVar('SECTION', $section);
	$_TEMPLATE['SECTION'] = $section;
	$_TEMPLATE['PAGE_TITLE'] = ucwords(preg_replace('/_/', ' ', $section));
	$_TEMPLATE['SECTION_URL'] = $sectionUrl;
	$_TEMPLATE['PAGE_URL'] = $pageUrl;
}

if(isset($_GET['alert']) && !empty($_GET['alert'])) {
	$msg = new Message(false);
	$alertBody = urldecode($_GET['alert']);
	$msg->title = "Notice";
	if(isset($_GET['alerttype'])) {
		switch(strtolower($_GET['alerttype'])) {
			case 'alert':
			case 'success':
				addAlert("Success", $alertBody, Message::TYPE_STATUS);
			break;
		
			case 'error':
			default:
				addAlert("Error", $alertBody, Message::TYPE_ERROR);
		}
	}
	$implodeThis = $_GET;
	unset($implodeThis['u'], $implodeThis['alert']);
	$goHere = $pageUrl .'?'. http_build_query($implodeThis);
	if(!debugPrint($goHere, "going to redirect...")) {
		ToolBox::conditional_header($goHere);
	}
	exit;
}
else {
	$_adminTmpl->addVar('alertHidden', 'hidden');
}

// Check for access.
if(!empty($section)) {
//	$oldSetting = ToolBox::$debugPrintOpt;
//	ToolBox::$debugPrintOpt = 1;
	
	if($acl->canModify($section)) {
		/*
		 * The included files like to echo stuff.  So capture what's been echo'ed 
		 * and stuff it into a template var.
		 */
		ob_start();
		if(file_exists(BASE .'/_app/admin/'. $useThis)) {
			require(BASE .'/_app/admin/'. $useThis);
		}
		$_adminTmpl->addVar('ADMIN_CONTENT', ob_get_contents());
		ob_end_clean();
	}
	else {
		addAlert("Access Denied", $acl->accessDeniedMsg(false) ." Section: '". $section ."'", 'error');
		debugPrint($acl->hasAccess($section), "hasAccess check");
		if(!debugPrint("<a href='{$adminUrl}'>{$adminUrl}</a>", "woulda redirected, but you're debugging")) {
			ToolBox::conditional_header($adminUrl);
		}
		exit;
	}
//	ToolBox::$debugPrintOpt = $oldSetting;
}


// For testing (ensuring styles are good for alerts)
if(!empty($_GET['testalert'])) {
	$types = array(Message::TYPE_FATAL, Message::TYPE_ERROR, Message::TYPE_STATUS, Message::TYPE_NOTICE);
	if(in_array($_GET['testalert'], $types)) {
		$type = $_GET['testalert'];
		addAlert("Test Alert (". $type .")", "This is a test alert, of type '". $type ."'... to see 'em all, click <a href='?testalert=1'>here</a>", $type);
	}
	else {
		foreach($types as $type) {
			addAlert("Test Alert (". $type .")", "This is a test alert, of type '". $type ."'... to see just this one, click <a href='?testalert=".$type."'>here</a>", $type);
		}
	}
}

// if there's a message, create it and then clear the queue.
$que = new MessageQueue(true);
if($que->getCount() > 0) {
	$alertTmpl = getTemplate('bits/alert.html');
	$_adminTmpl->addVar('ALERT', $alertTmpl->renderRows($que->getAll()));
	$que->clear();
}

//if content is being set two different ways, make sure all the content gets exposed.
if(isset($_TEMPLATE['ADMIN_CONTENT'])) {
	$old = $_adminTmpl->templates['ADMIN_CONTENT'];
	$_TEMPLATE['ADMIN_CONTENT'] = $old . $_TEMPLATE['ADMIN_CONTENT'];
}

// set the logo and color.
$_TEMPLATE['logopath'] = '/update/_elements/images/logo.png';
$defaultHeaderColor = '#000';
$logoPath = $settings->get('logo_image', 'site');
if(!empty($logoPath)) {
	debugPrint($logoPath, "logo path from settings");
	$mediaObj = new media($db);
	$info = $mediaObj->getByFilename($logoPath);
	debugPrint($info, "all info about logo file");
	$_TEMPLATE['logopath'] = $info['path'] . $info['filename'];
}
debugPrint($_TEMPLATE['logopath'], "path to admin logo");


if(is_array($_TEMPLATE)) {
	$_adminTmpl->addVarList($_TEMPLATE);
}

if(isset($_adminTmpl->templates['PAGE_TITLE'])) {
	$_adminTmpl->addVar('HTML_TITLE', $_adminTmpl->templates['PAGE_TITLE'] .' | ');
}

echo $_adminTmpl->render(true);
