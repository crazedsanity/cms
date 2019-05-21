<?php

namespace cms\cms\core;

class acl extends core {

	// NOTE:: these constants should always match what's in core.php (unless a better system is implemented)
	const ADD		= 4;
	const EDIT		= 6;
	const DELETE	= 7;
	
	/** cache data to avoid performance penalties */
	protected $_dataCache = array();
	
	/** store the number of calls (indexed by method name); helps determine the source of performance problems */
	protected $_callCache = array();

	function __construct($db){
		$this->db = $db;
	}
	
	
	/**
	 * Determine if the user is valid (and translate username to user_id).
	 * 
	 * NOTE: this caches data to avoid performance penalties.
	 * 
	 * @param mixed $username
	 * @return int
	 */
	public function isValidUser($username) {
		$this->_doCache(__METHOD__, 'actual');
		
		if(isset($this->_dataCache[__METHOD__])) {
			$this->_doCache(__METHOD__, 'cached');
			
			$isValid = false;
			
			if(is_numeric($username)) {
				$record = $this->_dataCache[__METHOD__][$username];
			}
			else {
				foreach($this->_dataCache[__METHOD__] as $k=>$v) {
					if($v['username'] == $username) {
						$record = $v;
						break;
					}
				}
			}
			
			$isValid = $record['user_id'];
			
			return $isValid;
		}
		else {
			// cache all the data.
			$sql = "SELECT user_id, name, username, email, created, modified FROM users";
			$this->db->run_query($sql, array());
			
			$this->_dataCache[__METHOD__] = $this->db->farray_fieldnames('user_id');
			
			// Call again, but use the cache.
			return $this->isValidUser($username);
		}
		
		return $isValid;
	}
	
	protected function _doCache($method, $index='default') {
		if(!isset($this->_callCache[$method])) {
			$this->_callCache[$method] = array();
		}
		if(!isset($this->_callCache[$method][$index])) {
			$this->_callCache[$method][$index] = array();
			$this->_callCache[$method][$index] = 0;
		}
		$this->_callCache[$method][$index]++;
	}
	
	
	public function getUserGroups($userId) {
		$this->_doCache(__METHOD__, 'actual');
		
		$myId = $this->isValidUser($userId);
		$groups = array();
		
		if($myId !== false && is_numeric($myId) && intval($myId) > 0) {
			$params = array('id'=>$myId);
			$sql = 'SELECT * FROM user_groups WHERE user_id=:id';
			
			$numRows = $this->db->run_query($sql, $params);
			if($numRows > 0) {
				$data = $this->db->farray_fieldnames();
				foreach($data as $k=>$v) {
					$groups[] = $v['group_id'];
				}
			}
		}
		
		return $groups;
	}
	
	
	function access($userId, $asset, $assetId = 0, $permission=self::EDIT){
		$this->_doCache(__METHOD__);
		
		$hasAccess = false;
		
		$isValidUser = $this->isValidUser($userId);
		
		if($isValidUser !== false && intval($isValidUser) > 0) {
			$userId = $isValidUser;

			$validGroups = implode(',', $this->getUserGroups($userId));

			if(!$assetId){
				$assetId=0;
			}
			$cleanAsset = filter_var($asset, FILTER_SANITIZE_STRIPPED);
			$params = array(
//				'aid'		=> $assetId,
//				'asset'		=> "'". $asset ."'",
//				'perm'		=> $permission,
//				'groups'	=> $validGroups,
			);
			$sql="
				SELECT *
				FROM acl
				WHERE group_id IN ({$validGroups})
					AND (asset_id = {$assetId} OR asset_id = 0)
					AND asset = '{$cleanAsset}'
					AND permission = {$permission}
					";
					$this->debugPrint($sql, "SQL");

			$numRecords = $this->db->run_query($sql);
			$this->debugPrint($numRecords, "number of records");
			if($numRecords > 0){
				$hasAccess = true;
				$this->debugPrint($hasAccess, "user CAN access '". $asset ."'");
			}
			else {
				$this->debugPrint($hasAccess, "user CANNOT access '". $asset ."'");
			}
		}
		
		return $hasAccess;
	}

	public function hasAdd($asset, $asset_id = 0) {
		$this->_doCache(__METHOD__);
		return $this->access($_SESSION['MM_Username'], $asset, $asset_id, self::ADD);
	}

	public function hasEdit($asset, $asset_id = 0) {
		$this->_doCache(__METHOD__);
		return $this->access($_SESSION['MM_Username'], $asset, $asset_id, self::EDIT);
	}

	public function hasDelete($asset, $asset_id = 0) {
		$this->_doCache(__METHOD__);
		return $this->access($_SESSION['MM_Username'], $asset, $asset_id, self::DELETE);
	}

	public function hasAccess($asset, $asset_id = 0) {
		$this->_doCache(__METHOD__);
		$result = $this->canModify($asset, $asset_id);
		return $result;
	}

	public function canModify($asset, $asset_id = 0) {
		$this->_doCache(__METHOD__);
		
		$hasAdd = $this->access($_SESSION['MM_Username'], $asset, $asset_id, self::ADD);
		$hasEdit = $this->access($_SESSION['MM_Username'], $asset, $asset_id, self::EDIT);

		$result = ($hasAdd || $hasEdit);

		return $result;
	}
	

	public static function accessDeniedMsg($useHtml = true) {
		$msg = 'You do not have access to this area.  If this is a mistake please contact your site administrator.';
		if($useHtml) {
			$msg = "<p style='color: #f00; font-weight: bold;'>{$msg}</p>";
		}
		return $msg;
	}
	
	
	public function isGroupMember($groupId) {
		$isMember = false;
		
		$groups = $this->getUserGroups($_SESSION['MM_UserID']);
		$this->debugPrint($groups);
		if(in_array($groupId, $groups)) {
			$isMember = true;
		}
		
		
		return $isMember;
	}
}

