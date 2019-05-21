<?php
/*
 * This script was created to help setup a new instance of KKCMS. 
 * 
 * 
 */

ini_set('display_errors', false);
//require_once(__DIR__ .'/../_app/core.php');
require_once(__DIR__ .'/../vendor/autoload.php');
require_once(__DIR__ .'/../_app/utility.php');
//require_once(__DIR__ ."/../_app/database.php");

ini_set('display_errors', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

use crazedsanity\core\ToolBox;
use cms\cms\core\Database;

ToolBox::$debugPrintOpt = 1;
ToolBox::$debugRemoveHr = 1;

debugPrint("Debug printing is on");

$useIni = false;
if(file_exists(__DIR__ . '/../config/siteconfig-dev.ini')) {
	$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig-dev.ini',true);
}
elseif(file_exists(__DIR__ . '/../config/siteconfig.ini')) {
	$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig.ini', true);
}
else {
	throw new Exception("Cannot connect to database, no configuration supplied.");
}


$db = new Database(
	$useIni['database']['server'],
	$useIni['database']['user'],
	$useIni['database']['pass'],
	''
//	$useIni['database']['database']
);

try {
	debugPrint(
		$db->run_query("DROP DATABASE IF EXISTS ". $useIni['database']['database']), 
		"result of dropping database"
	);
	debugPrint(
		$db->run_query("create database ". $useIni['database']['database']),
		"result of creating database"
	);
}
catch(Exception $ex) {
	debugPrint($ex->getMessage(), "Error creating database");
//	exit(__FILE__ ." - line #". __LINE__ ."\n");
}

// connect to the new database
$db = new Database(
	$useIni['database']['server'],
	$useIni['database']['user'],
	$useIni['database']['pass'],
	$useIni['database']['database']
);




// Now load upgrades.
$loadThese = array();
$dir = new DirectoryIterator(__DIR__ .'/../upgrades/');
foreach($dir as $fileinfo) {
	if(!$fileinfo->isDot()) {
//		debugPrint($fileinfo, "file info");
		$loadThese[] = $fileinfo->getFilename();
	}
}

// make sure they get loaded in the correct order.
sort($loadThese);


// load schema and data first
array_unshift($loadThese, '../setup/data.sql');
array_unshift($loadThese, '../setup/setup.sql');


debugPrint($loadThese, "files to be loaded");
foreach($loadThese as $file) {
//	$fRes = $db->run_sql_file(__DIR__ .'/../upgrades/'. $file);
//	debugPrint($fRes, "result of loading file ". $file);
	
	/*
	 * To ensure these scripts actually execute (like if they have a 
	 * "DELIMITER" statement, as is the case with creating a function), they 
	 * are run through a shell command... because MySQL is "so awesome".
	 * 
	 * MORE INFO: https://stackoverflow.com/a/4028289
	 */
	$scriptPath = __DIR__ .'/../upgrades/'. $file;
	
	
	/**
	 * 
	 * ========================================================================
	 * WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!! 
	 * ========================================================================
	 * 
	 * 
	 * The *.sql files should NOT contain statements that specify a database (schema)!!!
	 * LEAVING SCHEMA IN THE STATEMENT CAN ALTER THE **WRONG** DATABASE IN VERY 
	 * UNEXPECTED AND EXTREMELY DANGEROUS WAYS!!!
	 * 
	 * (EXAMPLE: "`production`.`table-name`")
	 *
	 * TODO: do a quick & dirty preg_match() on the contents to check for schema.table syntax.
	 * 
	 * -------
	 * 
	 * Comments must start at the beginning of the line, with only two dashes.
	 * THREE WILL CAUSE AN UNEXPECTED ERROR.
	 * 
	 * 
	 * ========================================================================
	 * WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!! 
	 * ========================================================================
	 * 
	 * 
	 */
	
	
	$command = "mysql -u{$useIni['database']['user']} ";
	if(isset($useIni['database']['pass']) && strlen($useIni['database']['pass'])) {
		$command .= "-p{$useIni['database']['pass']} ";
	}
	$command .= "-h {$useIni['database']['server']} -D {$useIni['database']['database']} < {$scriptPath} ";
	$command .= " 2>&1"; // make sure STDERR and STDOUT are merged, so we can properly test for errors.
	$output = shell_exec($command);
	
	debugPrint($output, "result of running ({$file}) via shell_exec()");
	if(preg_match('/ERROR /', $output) !== 0) {
		/*
		 * If this happens, it will likely look a bit silly (the output will appear on 
		 * the same line as the command prompt).  THIS IS BY DESIGN, it's a visual cue 
		 * that something went wrong.
		 */
		echo("ABORT, ABORT!  SOMETHING MUST HAVE JUST FAILED.... \n". __FILE__ ." - line #". __LINE__); 
		exit(1);
	}
}


// Give the admin group full permission for everything.
if($db->run_query("SELECT * FROM groups WHERE group_id=1") == 1) {
	$db->run_query("SELECT * FROM assets ORDER BY asset_id");
	$allAssets = $db->farray_fieldnames();
	foreach($allAssets as $oneAsset) {
//		debugPrint($oneAsset, "asset record");
		if($db->run_query("SELECT * FROM acl WHERE group_id=1 AND asset=:ass", array('ass'=>$oneAsset['name'])) == 0) {
			
			$insertData = array(
				'user_id'		=> 0,
				'group_id'		=> 1,
				'asset'			=> $oneAsset['name'],
				'asset_id'		=> 0,
				'permission'	=> 4,
			);
			
			debugPrint($db->insert('acl', $insertData), "creating perm 4 for ". $oneAsset['name']);
			$insertData['permission'] = 6;
			debugPrint($db->insert('acl', $insertData), "creating perm 6 for ". $oneAsset['name']);
			$insertData['permission'] = 7;
			debugPrint($db->insert('acl', $insertData), "creating perm 7 for ". $oneAsset['name']);
		}
	}
}

