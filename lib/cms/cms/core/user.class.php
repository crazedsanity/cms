<?php

namespace cms\cms\core;


use crazedsanity\core\ToolBox;
use \Exception;
use \InvalidArgumentException;
use \PDOException;


class User extends core {
	public $MM_authorizedUsers = "";
	public $MM_donotCheckaccess = "true";
	public $MM_fldUserAuthorization = "";
	public $MM_redirectLoginSuccess = '/update/';
	public $table = "users";
	public $superadmin = 0;
	public $subdirectory = '';
	public $MM_restrictGoTo = '/update/login.php';

	public $db;

	const AUTH_OK = true;
	const AUTH_ERROR_PASSWORD = 0;
	const AUTH_ERROR_EMPTY = 1;
	const AUTH_ERROR_INACTIVE = 2;
	const AUTH_ERROR_OTHER = -1;

	public function __construct($db) {
		$this->db = $db;
		parent::__construct($db, 'users', 'user_id', 'user_id');
	}


	// *** Restrict Access To Page: Grant or deny access to this page
	public function isAuthorized( $strUsers, $strGroups, $UserName, $UserGroup ) {
		debugPrint(func_get_args(), __METHOD__ ." - arguments");
		// For security, start by assuming the visitor is NOT authorized. 
		$isValid = False;

		// When a visitor has logged into this site, the Session variable MM_Username set equal to their username. 
		// Therefore, we know that a user is NOT logged in if that Session variable is blank. 
		if ( !empty( $UserName ) ) {
			// Besides being logged in, you may restrict access to only certain users based on an ID established when they login. 
			// Parse the strings into arrays. 
			$arrUsers = Explode( ",", $strUsers );
			$arrGroups = Explode( ",", $strGroups );
			if ( in_array( $UserName, $arrUsers ) ) {
				$isValid = true;
			}
			debugPrint($UserGroup, __METHOD__ ." - the needle");
			debugPrint($arrGroups, __METHOD__ ." - the haystack");
			// Or, you may restrict access to only certain users based on their username. 
			if ( in_array( $UserGroup, $arrGroups ) ) {
				$isValid = true;
			}
			if ( ( $strUsers == "" ) && true ) {
				$isValid = true;
			}
		}
		return $isValid;
	}

	public function login($loginUsername, $password) {
		debugPrint(func_get_args(), __METHOD__ ." - arguments");

		$retval = self::AUTH_ERROR_OTHER;

		// Form Action
		if ( isset( $_GET['accesscheck'] ) ) {
			$_SESSION['PrevUrl'] = $_GET['accesscheck'];
		}

		if (!empty($loginUsername) && !empty($password)) {
			$MM_redirectLoginFailed = $this->subdirectory . "/update/login.php?alert=" . urlencode( "Invalid username or password" );

			$sql = "SELECT user_id, username, password, is_active FROM " . $this->table 
					. " WHERE username=:user";

			$params = array(
				'user'	=> $loginUsername,
			);
			debugPrint($sql, __METHOD__ ." - SQL");
			debugPrint($params, __METHOD__ ." - params");
			try {
				$num = $this->db->run_query($sql, $params);
				debugPrint($num, __METHOD__ ." - number of records found");
				$LoginRS = $this->db->get_single_record();
				debugPrint($LoginRS, __METHOD__ ." - record");
				if(is_array($LoginRS)) {

					// check to make sure the password matches.
					if(password_verify($password, $LoginRS['password'])) {

						if($LoginRS['is_active'] == 1) {
							//declare two session variables and assign them
							$_SESSION['MM_Username'] = $LoginRS['username'];
							$_SESSION['MM_UserID'] = $LoginRS['user_id'];

							$retval = self::AUTH_OK;
						}
						else {
							$retval = self::AUTH_ERROR_INACTIVE;
						}
					}
					else {
						$retval = self::AUTH_ERROR_PASSWORD;
						debugPrint($retval, __METHOD__ ." - password didn't match");
					}
				}
				debugPrint($LoginRS, __METHOD__ ." - Login info");
			} catch (Exception $ex) {
				// TODO: Log this!
				debugPrint($ex->getMessage(), __METHOD__ ." - exception");
			}
		}
		else {
			$retval = self::AUTH_ERROR_EMPTY;
		}
		debugPrint($retval, __METHOD__ ." - returning value");
		return $retval;
	}

	public function logout($doRedirect=true) {
		// *** Logout the current user.
		if ( $this->superadmin ) {
			$logoutGoTo = $this->subdirectory . "/update/login.php";
		} else {
			$logoutGoTo = $this->subdirectory . "/";
		}
		if ( !isset( $_SESSION ) ) {
			session_start();
		}
		unset( $_SESSION);
		if($doRedirect) {
			if ( $logoutGoTo != "" ) {
				header( "Location: $logoutGoTo" );
				exit;
			}
		}
	}

	public function restrict() {
		if ( $this->superadmin ) {
			$this->MM_restrictGoTo = $this->subdirectory . '/update/login.php';
		} else {
			$this->MM_restrictGoTo = $this->subdirectory . '/update/login.php';
		}
		debugPrint($_SESSION, __METHOD__ ." - session info");
		if(!((isset($_SESSION['MM_Username'])) && ($this->isAuthorized( "", $this->MM_authorizedUsers, $_SESSION['MM_Username'], $_SESSION['MM_UserGroup'] ) ) ) ) {
			$MM_qsChar = "?";
			$MM_referrer = $_SERVER['PHP_SELF'];
			if ( strpos( $this->MM_restrictGoTo, "?" ) )
				$MM_qsChar = "&";
			if ( isset( $QUERY_STRING ) && strlen( $QUERY_STRING ) > 0 ) $MM_referrer .= "?" . $QUERY_STRING;
			$this->MM_restrictGoTo = $this->MM_restrictGoTo . $MM_qsChar . "accesscheck=" . urlencode( $MM_referrer );
			header( "Location: " . $this->MM_restrictGoTo );
			exit;
		}
	}


	public function checkLogin() {
		$retval = false;
		if(isset($_SESSION['MM_UserID']) && is_numeric($_SESSION['MM_UserID'])) {

			$sql = "SELECT user_id, username, password FROM " . $this->table 
					. " WHERE user_id=:id AND is_active=1";

			$params = array('id'=>$_SESSION['MM_UserID']);
			$this->db->run_query($sql, $params);
			$LoginRS = $this->db->get_single_record();

			if(is_array($LoginRS) && isset($LoginRS['user_id']) && $LoginRS['user_id'] == $_SESSION['MM_UserID']) {
				$retval = true;
			}
			else {
				// Maybe they got deactivated.
				$this->logout(false);
				$retval = false;
			}
		}

		return $retval;
	}


	/**
	 * Retrieves groups for a user ID; defaults to the user designated by 
	 * the current session, but specifying an integer will query for that 
	 * one instead.
	 * 
	 * @param (mixed) $userId	User ID to query for, defaults to session data.
	 */
	public function getGroups($userId=true) {
		$retval = null;

		$id = null;
		if($userId === true || $userId === null) {
			if(isset($_SESSION['MM_UserID']) && intval($_SESSION['MM_UserID']) > 0) {
				$id = intval($_SESSION['MM_UserID']);
			}
			else {
				throw new InvalidArgumentException("No usable ID in session");
			}
		}
		elseif(!is_bool($userId) && is_numeric($userId) && intval($userId) > 0) {
			$id = intval($userId);
		}
		else {
			throw Exception("No valid User ID found/supplied");
		}

		$params = array('id' => $id);
		$sql = "
			SELECT 
				 g.group_id
				,u.name
				,u.username
				,u.email
				,u.created
				,u.modified
				,g.name AS group_name
			FROM users AS u
				INNER JOIN user_groups AS ug ON (u.user_id=ug.user_id)
				INNER JOIN groups AS g ON (ug.group_id=g.group_id)
			WHERE
				u.user_id=:id";

		try {
			debugPrint($sql, __METHOD__ ." - sql");
			$this->db->run_query($sql, $params);
			$retval = $this->db->farray_fieldnames('group_name');
			debugPrint($retval, __METHOD__ ." - returned data");
		} 
		catch (PDOException $ex) {
			// TODO: log this...
			debugPrint($ex->getMessage(), __METHOD__ ." - exception");
			throw new Exception("Error occurred while trying to run query: ". $ex->getMessage());
		}

		return $retval;
	}


	public function checkIsAdmin() {
		$isAdmin = false;
		$groupName = 'admin';
		debugPrint($groupName, __METHOD__ ." - checking against group name");
		try {
			$groups = $this->getGroups(true);
			debugPrint($groups, __METHOD__ ." - groups to check against");
			foreach($groups as $id=>$data) {
				$checkThis = strtolower(trim($data['group_name']));
				debugPrint($checkThis, __METHOD__ ." - checking for equality (vs '". $groupName ."')");
				debugPrint($data, __METHOD__ ." - group data");
				if($checkThis == $groupName) {
					$isAdmin = true;
					break;
				}
			}
		} 
		catch (Exception $ex) {
			// TODO: log this...
		}

		return $isAdmin;
	}
	
	
	public function hasCmsAccess() {
		
		$hasAccess = false;
		$groupName = 'cms';
		debugPrint($groupName, __METHOD__ ." - checking against group name");
		try {
			$groups = $this->getGroups(true);
			debugPrint($groups, __METHOD__ ." - groups to check against");
			foreach($groups as $id=>$data) {
				$checkThis = strtolower(trim($data['group_name']));
				debugPrint($checkThis, __METHOD__ ." - checking for equality (vs '". $groupName ."')");
				debugPrint($data, __METHOD__ ." - group data");
				if($checkThis == $groupName) {
					$hasAccess = true;
					break;
				}
			}
		} 
		catch (Exception $ex) {
			// TODO: log this...
		}

		return $hasAccess;
	}


	public function lookupUser($lookFor, $field='email') {
		$validLookupFields = array('username', 'email', 'user_id');
		if(!is_null($lookFor) && in_array($field, $validLookupFields)) {
			$params = array(
				'val'	=> $lookFor,
			);
			$sql = "SELECT user_id, name, username, email FROM users WHERE ". $field .' = :val';

			debugPrint($sql, __METHOD__ ." - sql");
			$numRows = $this->db->run_query($sql, $params);
			debugPrint($numRows, __METHOD__ ." - found records");

			$data = null;
			if($numRows == 1) {
				$data = $this->db->get_single_record();
				debugPrint($data, __METHOD__ ." - record");

				$data['user_id'];
			}
		}
		else {
			throw new InvalidArgumentException;
		}

		return $data;
	}
	
	
	public function insert(array $data) {
		
		if(isset($data['password'])) {
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		}
		if(isset($data['user_id'])) {
			unset($data['user_id']);
		}
		$data['created'] = "NOW()";
		
		return parent::insert($data);
	}
	
	
	public function update(array $data, $whereId) {
		if(isset($data['password'])) {
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		}
		
		return parent::update($data, $whereId);
	}
	
	
	public function getAll($orderBy = null, array $constraints = null) {
		$this->debugPrint(func_get_args(), "Arguments");
		$sql = "SELECT 
			 u.*
			,ug.group_id
			,g.name as group_name
			,g.description as group_description
		FROM users u
		LEFT JOIN user_groups ug ON ug.user_id = u.user_id
		LEFT JOIN groups g ON g.group_id = ug.group_id
		";
		
		if(is_null($orderBy)) {
			$orderBy = "g.name, u.name, u.username";
		}
		
		$params = array();
		
		if(is_array($constraints)) {
			$constraintSql = '';
			foreach($constraints as $k=>$v) {
				$paramKey = preg_replace('~\.~', '_', $k);
				$constraintSql = ToolBox::create_list($constraintSql, "{$k} LIKE :{$paramKey}", " AND ");
				$params[$paramKey] = $v;
			}
			$sql .= ' WHERE '. $constraintSql;
		}
		
		if(!is_null($orderBy)) {
			$sql .= " ORDER BY {$orderBy}";
		}
		elseif(!is_null($this->defaultOrder)) {
			$sql .= " ORDER BY {$this->defaultOrder}";
		}
		$this->debugPrint($sql, "SQL");
		$this->debugPrint($params, "Parameters");
		
		$this->db->run_query($sql, $params);
		
		$data = $this->db->farray_fieldnames();
		
		return $data; 
	}
}


