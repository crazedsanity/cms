<?php

require_once( __DIR__ ."/../../_app/core.php");
error_reporting(E_ALL);
ini_set('display_errors', true);

use crazedsanity\core\ToolBox;
use crazedsanity\messaging\Message;
use crazedsanity\messaging\MessageQueue;

ToolBox::$debugPrintOpt = 1;

$user->superadmin = 1;

if(!empty($_POST)) {
	$goHere = '/update/login.php';
	$loginRes = $user->login($_POST['usrname'], $_POST['passwrd']);
	debugPrint($loginRes, "Login Res");
	
	if($loginRes === true) {
		if($user->hasCmsAccess()) {
			// they're an admin, proceed as normal.
			addAlert("Login Successful", "You logged in successfully.", "notice");
			$goHere = '/update/';
		}
		else {
			// so, basically, a regular user tried getting to admin.
			$goHere = "/";
			addAlert("Access Dened", "You don't have permission to access this section of the site.", "error");
		}
	}
	else {
		if(debugPrint($loginRes, "login result")) {
			debugPrint($_SESSION, "SESSION");
			exit(__FILE__ ." line #". __LINE__);
		}
		else {
			addAlert("Login Failed", "Failed to login. Please try again.", 'error');
		}
	}
	
	if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "You're debugging... so here's the link to drop POST")) {
		ToolBox::conditional_header($goHere);
	}
	exit;
}


$_loginTmpl = getTemplate('login.tmpl');
$_loginTmpl->addVar('TITLE_DIVIDE', TITLE.DIVIDE);
$_loginTmpl->addVar('logoLink', 'update/');


// if there's a message, create it and then clear the queue.
$que = new MessageQueue(true);
if($que->getCount() > 0) {
	$alertTmpl = getTemplate('bits/alert.html');
	$_loginTmpl->addVar('ALERT', $alertTmpl->renderRows($que->getAll()));
	$que->clear();
}



$title='Login';
if(isset($_GET['accesscheck'])){
	$user->MM_redirectLoginSuccess=urldecode($_GET['accesscheck']);
}

echo $_loginTmpl->render(true);
