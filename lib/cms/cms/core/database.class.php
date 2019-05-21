<?php

namespace cms\cms\core;

/**
 * Original info::::
 *			# Name: Database.class.php
 *			# File Description: MySQL Class to allow easy and clean access to common mysql commands
 *			# Author: ricocheting
 *			# Web: http://www.ricocheting.com/
 *			# Update: 2010-05-08
 *			# Version: 3.1.3
 *			# Copyright 2003 ricocheting.com
 * 
 *  
 * 
 * Now, this is a wrapper for the database in vendor/crazedsanity/database/. See 
 * associated MIT license.
 * 
 * Further notices of copyright, etc. are removed.
 */

class Database extends \crazedsanity\database\Database {

	/** debug flag for showing error messages */
	public $debug = true;
	private $server = DB_SERVER;   //database server
	private $user = DB_USER;   //database login name
	private $pass = DB_PASS;   //database login password
	private $database = DB_DATABASE;  //database name
	private $error = "";

	/** number of rows affected by SQL query */
	public $affected_rows = 0;
	public $history = array(
		'sql'		=> array(),
		'params'	=> array(),
		'timing'	=> array(),
		'errors'	=> array()
	);

	
	
	public function __construct($server, $user, $pass, $database) {
		$dsn = "mysql:host=". $server .";dbname=". $database;
		parent::__construct($dsn, $user, $pass);
		
		$this->server = $server;
		$this->database = $database;
		$this->user = $user;
		$this->pass = $pass;
	}
	
	public static function obtain() {
		throw new BadFunctionCallException(__METHOD__ ." is no longer used");
	}

	public function connect($new_link = false) {
		$this->reconnect($this->dsn, $this->user, $this->pass);
	}
	
	

	public function close() {
		$this->close();
	}

	
	public function escape($string) {
		if (get_magic_quotes_runtime())
			$string = stripslashes($string);
		return mysql_escape_string($string);
	}

	
	public function query($sql, $type=null, array $params=null) {
		$this->history['sql'][] = $sql;

		$debugIndex = count($this->history['sql']) -1;
		
		if(is_array($params)) {
			$this->history['params'][$debugIndex] = $params;
		}

		// do query
		$start = microtime(true);
		
		switch(strtolower($type)) {
			case 'insert':
				$result = $this->run_insert($sql, $params);
				break;
			case 'update':
				$result = $this->run_update($sql, $params);
				break;
			default:
				$result = $this->run_query($sql, $params);
		}
		
		$totalTime = number_format(microtime(true) - $start,5);
		$this->history['timing'][$debugIndex] = $totalTime;
		
		$error = $this->errorMsg();
		if(!empty($error)) {
			$this->history['errors'][$debugIndex] = $error;
		}
		
		$this->checkForErrors();

		return $result;
	}
	
	
	public function query_first($query_string) {
//		$this->run_query($query_string);
		$this->query($query_string);
		return $this->get_single_record();
	}
	
	
	public function fetch($query_id = -1) {
		return $this->farray_fieldnames();
	}
	
	
	
	public function fetch_array($sql) {
		$this->query($sql);
		return $this->farray_fieldnames();
	}
	
	
	
	/**
	 * Index the array based on a field specified by $indexField... 
	 *  so the primary key field can be the index, and the sub-data is
	 *  what's in that associated record.  For "records" where the primary key
	 *  is "record_id", call:
	 *        $out = Database::fetch_array_assoc($sql, "record_id")
	 *  and the data for record_id=5 can be accessed by 
	 *		  $myData = $out[5];
	 * instead of iterating over the whole set looking for that record.
	 * @returns 
	 */
	public function fetch_array_assoc($sql, $indexField) {
		$this->query($sql);
		return $this->farray_fieldnames($indexField);
	}
	
	
	
	public function update($table, $data, $where = '1') {
		$q = "UPDATE `$table` SET ";
		
		$params = array();
		foreach ($data as $key => $val) {
			if (strtolower($val) == 'null') {
				$q.= "`$key` = NULL, ";
			}
			elseif (strtolower($val) == 'now()') {
				$q.= "`$key` = NOW(), ";
			}
			elseif (preg_match("/^increment\((\-?\d+)\)$/i", $val, $m)) {
				$q.= "`$key` = `$key` + $m[1], ";
			}
			else {
				$q.= "`$key`=:$key, ";
				$params[$key] = $val;
			}
		}

		$q = rtrim($q, ', ') . ' WHERE ' . $where . ';';
		
		return $this->query($q, __FUNCTION__, $params);
	}
	
	
	
	public function insert($table, $data) {
		$q = "INSERT INTO `$table` ";
		$v = '';
		$n = '';
		
		$params = array();

		foreach ($data as $key => $val) {
			$n.="`$key`, ";
			if (strtolower($val) == 'null') {
				$v.="NULL, ";
			}
			elseif (strtolower($val) == 'now()') {
				$v.="NOW(), ";
			}
			else {
				$v .= ":$key, ";
				$params[$key] = $val;
			}
		}

		$q .= "(" . rtrim($n, ', ') . ") VALUES (" . rtrim($v, ', ') . ");";
		
		return $this->query($q, __FUNCTION__, $params);
	}
	
	
	
	private function free_result() {
		throw new BadMethodCallException(__METHOD__ ." is no longer used");
	}
	
	
	
	private function oops($msg = '') {
		throw new BadMethodCallException(__METHOD__ ." is no longer used");
	}
	
	
	public function checkForErrors() {
		$errors = 0;
		if(isset($this->history['errors']) && count($this->history['errors']) > 0) {
			$x = array_keys($this->history['errors']);
			$theKey = $x[count($x) -1];
			
			if(isset($this->history['sql'][$theKey])) {
				$e = new \Exception();
				$brokenSql = $this->history['sql'][$theKey];
				$dbError = $this->history['errors'][$theKey];
				debugPrint(preg_replace("~{$_SERVER['DOCUMENT_ROOT']}~", '', $e->getTraceAsString()), "backtrace");
				debugPrint($dbError, __METHOD__ ." - the error");
				debugPrint($brokenSql, "the broken SQL");
				$errors = 1;
			}
		}
		return $errors;
	}

}

