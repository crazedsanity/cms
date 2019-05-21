<?php
/// NOTE: see *below* class definition for where it gets called.
/*
 * 
 * This script was written to be flexible, though that requires some complexity.  
 * Sorry about that.
 * 
 * EXAMPLE COMMAND LINE:
 * php ./import_users_from_csv.php /path/to/file.csv NameFirst=name,NameLast=name,EnrollmentNumber=tribal_id,Email=email,NameFirst=username,NameLast=username
 * 
 * BREAKDOWN:
 * 
 * arg #1: /path/to/file.csv
 *		(This is the path to the CSV that contains a list of user information.)
 * 
 * arg #2: NameFirst=name,NameLast=name,EnrollmentNumber=tribal_id,Email=email,NameLast=username,NameFirst=username
 *		(This maps fields in the CSV (first line should be headers) to columns in the database.)
 *	
 *		SYNTAX: (CSV fieldname)=(database_column)
 *		Things are comma-delimited, so it's a long list.
 *		When there are multiple CSV fields mapped to a single database column, the values are appended together
 *			(username puts a "_" between values, everything else uses a space).
 * 
 *		With the given value, it will:
 *	
 *		Set the "name" column to have a value of "{NameFirst} {NameLast}"
 *		Set the "tribal_id" column to have a value of "{EnrollmentNumber}"
 *		Set the "email" column to have a value of "{Email}"
 *		Set the "username" column to be something like "{namelast}_{namefirst}"
 * 
 */

ini_set('display_errors', false);
require_once(__DIR__ .'/../_app/core.php');

ini_set('display_errors', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

use crazedsanity\core\ToolBox;
use cms\cms\core\Database;
use cms\cms\core\User;

ToolBox::$debugPrintOpt = 1;

$useIni = false;
if(file_exists(__DIR__ . '/../config/siteconfig-dev.ini')) {
	$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig-dev.ini',true);
}
elseif(file_exists(__DIR__ . '/../config/siteconfig.ini')) {
	$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig.ini', true);
}






class _import extends cms\cms\core\core {
	
	protected $importDb;
	protected $db;
	
	private $_fh;
	private $_map;
	
	public function __construct(array $useIni, $filePath, $columnMap) {
		$this->db = new Database(
				$useIni['database']['server'],
				$useIni['database']['user'],
				$useIni['database']['pass'],
				$useIni['database']['database']
		);
		
		ToolBox::$debugRemoveHr = 1;
		
		$this->debugPrint(func_get_args(), "constructor arguments");
		
		// test that the file exists.
		if(is_file($filePath) && is_readable($filePath)) {
			$this->_fh = fopen($filePath, 'r');
		}
		else {
			throw new \InvalidArgumentException("Invalid or unreadable file (". $filePath .")");
		}
		
		$bits = explode(',', $columnMap);
		foreach($bits as $bit) {
			$f2v = explode('=', $bit);
			
			$key = $f2v[1];
			$val = $f2v[0];
			
			if(!isset($this->_map[$key])) {
				$this->_map[$key] = array();
			}
			$this->_map[$key][] = $val;
//			$this->_map[$f2v[0]] = $f2v[1];
		}
		
		$this->debugPrint($this->_map, "column map");
		
		// do the deed.
		$this->_insertUsers();
	}
	
	
	/**
	 * Map things into proper columns and clean as necessary.  Returned array 
	 * should be okay for insert.
	 * 
	 * @param array $newRecord
	 */
	protected function _cleanRecord(array $newRecord) {
		// put stuff into the appropriate indexes, so it can be inserted.
		$record = array();
		foreach($this->_map as $dbCol=>$colList) {
			foreach($colList as $findKey) {
				
				$rVal = $newRecord[$findKey];
				
				if(isset($record[$dbCol])) {
					$record[$dbCol] .= ' '. $rVal;
				}
				else {
					$record[$dbCol] = $rVal;
				}
			}
		}
		
		// clean each field.
		foreach($record as $k=>$v) {
			// clean it...
			switch($k) {
				case 'email':
					$record[$k] = trim(filter_var($v, FILTER_SANITIZE_EMAIL));
					break;
				case 'username':
					$record[$k] = preg_replace('/[^a-z0-9]+/', '_', trim(strtolower(filter_var($v, FILTER_SANITIZE_STRING))));
					break;
				default:
					$record[$k] = trim(filter_var($v, FILTER_SANITIZE_STRING));
			}
		}
		
		
		$this->debugPrint($record, "cleaned record");
		return $record;
	}
	
	
	protected function _insertUsers() {
		$u = new User($this->db);
		
		$headers = fgetcsv($this->_fh);
		foreach($headers as $k=>$name) {
			$headers[$k] = trim($name);
		}
		$this->debugPrint($headers, "First row (headers)");
		
		$counter = 0;
		$errors = 0;
		while(($data = fgetcsv($this->_fh, 1000, ",")) !== FALSE) {
			
			$passRecord = array();
			foreach($data as $k=>$val) {
				$passRecord[$headers[$k]] = $val;
			}
			$myRecord = $this->_cleanRecord($passRecord);
			
			// Create the user.
			try {
				$newId = $u->insert($myRecord);
				$counter++;
			} catch (Exception $ex) {
				$this->debugPrint($ex->getMessage(), "unable to insert user");
				$errors ++;
				
				if(preg_match('~Duplicate entry .+ for key \'username_UNIQUE~', $ex->getMessage()) == 1) {
					$lastError = $ex->getMessage();
					try {
//						
						$myRecord['username'] = $this->_findValidDupUser($myRecord['username']);
						
						$newId = $u->insert($myRecord);
						$counter++;
					}
					catch(Exception $ex) {
						$this->debugPrint($ex->getMessage(), "unable to recover from last error (". $lastError .")... \nUNRECOVERABLE ERROR: ");
						exit(__FILE__ ." - line #". __LINE__ ."\n");
					}
				}
				else {
					$this->debugPrint($ex->getTraceAsString(), "Could not find a way out of the error, giving up. Here is the trace");
					exit(__FILE__ ." - line #". __LINE__ ."\n");
				}
			}
			
		}
		
		$this->debugPrint($counter, "records created");
		$this->debugPrint($errors, "errors");
	}
	
	public function _findValidDupUser($originalUsername, $numCalls=null) {
		if(is_null($numCalls)) {
			$numCalls = 0;
		}
		$numCalls++;
		if($originalUsername !== null && !empty($originalUsername)) {
			$u = new User($this->db);
			$username = null;

			// try appending something to the username.
			$theBits = explode('-', $originalUsername);
			$lastBit = 0;
			if(count($theBits) > 0) {
				$lastBit = intval($theBits[count($theBits)-1]);
			}
			$lastBit++;
			$username = $theBits[0] .'-'. $lastBit;
			
			// try looking it up (this causes recursion, be careful.
			$userInfo = $u->lookupUser($username, 'username');
			
			if(!is_null($userInfo)) {
				// RECURSION!
				if($numCalls <= 100) {
					$username = $this->_findValidDupUser($username, $numCalls);
				}
				else {
					throw new exception("Failed to handle recursion");
				}
			}
			else {
//				$this->debugPrint($username, "found a good username");
//				exit;
			}
		}
		else {
			throw new InvalidArgumentException();
		}
						
		return $username;
	}
}


ToolBox::$debugPrintOpt = 1;

debugPrint($useIni, "ini settings");
debugPrint($argv, "CLI arguments");


$ImportObj = new _import($useIni, $argv[1], $argv[2]);
