<?php

$siteConfig = false;
if(file_exists(__DIR__ . '/../config/siteconfig-dev.ini')) {
	$siteConfig = parse_ini_file(__DIR__ . '/../config/siteconfig-dev.ini',true);
}
elseif(file_exists(__DIR__ . '/../config/siteconfig.ini')) {
	$siteConfig = parse_ini_file(__DIR__ . '/../config/siteconfig.ini', true);
}

if($siteConfig !== false) {
	define('DB_SERVER', $siteConfig['database']['server']);
	define('DB_DATABASE', $siteConfig['database']['database']);
	define('DB_USER', $siteConfig['database']['user']);
	define('DB_PASS', $siteConfig['database']['pass']);
}
else {
	define('DB_SERVER', $_SERVER['DB_HOST']);
	define('DB_USER', $_SERVER['DB_USER']);
	define('DB_PASS', $_SERVER['DB_PASS']);
	define('DB_DATABASE', $_SERVER['DB_DATABASE']);
}

$db = new Database(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE);
$db->debug = false;
$GLOBALS['db'] = $db;
