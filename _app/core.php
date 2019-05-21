<?php

//error_reporting(-1);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

$microtime = microtime();
define("BASE", __DIR__ .'/..');
define("ROOT", BASE .'/public_html');

require_once(BASE .'/vendor/autoload.php');

use crazedsanity\cms\core\User;

define("LIBDIR", __DIR__ . "/../lib");
define("CLASSES_DIR", __DIR__ . "/../lib");
define("TMPL_DIR", __DIR__ . "/../templates");
define("ASSETS_DIR", __DIR__ ."/../_includes/assets");

define("PUBLIC_UPFILES_DIR", '/data/upfiles/');
define("PUBLIC_MEDIA_DIR", PUBLIC_UPFILES_DIR .'media/');

define("UPFILES_DIR", ROOT . PUBLIC_UPFILES_DIR);
define("MEDIA_DIR", ROOT . PUBLIC_MEDIA_DIR);
define("THUMBS_DIR", UPFILES_DIR .'/thumbs');
define("IMG_DIR", ROOT .'/_elements/img');
#define("JQUERY", "1.x"); #Define and use if you want a certain version of jQuery, else defaults to latest version
define("ADD", 4);
define("EDIT", 6);
define("DELETE", 7);



require_once(BASE . "/_app/utility.php");
if(file_exists(BASE . "/_app/custom_functions.php")) {
	require_once(BASE . "/_app/custom_functions.php");
}


session_start();

require_once(LIBDIR ."/Database.class.php");
//require_once(LIBDIR ."/User.class.php");
require_once(LIBDIR ."/Base.class.php");
require_once(LIBDIR ."/Acl.class.php");
require_once(LIBDIR ."/Settings.class.php");

require_once(BASE . "/_app/database.php");
require_once(BASE . "/_app/cleaninput.php");

//some composer stuff, for external libraries.
if(file_exists(BASE .'/vendor/autoload.php')) {
	require_once(BASE .'/vendor/autoload.php');
}
if(isset($_GET['debug'])) {
	// set debug into the session, so form submissions and redirects retain it
	$_SESSION['debug'] = $_GET['debug'];
}

if(isset($_SESSION['debug'])) {
	define('DEBUGPRINT', $_SESSION['debug']);
}


$user = new \cms\cms\core\User($db);
//		User($db);


$acl = new acl($db);
$base = new Base($db);

$base->clean = $clean;
$base->mysql = $mysql;
$base->media_dir = MEDIA_DIR;

$settings = new settings($db, $base);

define("TITLE", $settings->get('title', 'site'));
define("DIVIDE", $settings->get('DIVIDE', 'site')); #Divider on Title

$_TEMPLATE = array();
$_TEMPLATE['JSHEAD'] = '';
$_TEMPLATE['CONTENT'] = '';

$vObj = new crazedsanity\version\Version(__DIR__ .'/../VERSION');
$_TEMPLATE['SITE_VERSION'] = trim($vObj->get_version());
