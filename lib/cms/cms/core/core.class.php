<?php

namespace cms\cms\core;

use \crazedsanity\core\ToolBox;
use \InvalidArgumentException;
use \Exception;
use \LogicException;
use cms\database\constraint;

/**
 * Because "Base" was already taken.
 */
class core {
	protected $db;
	
	protected $table;
	protected $pkey;
	protected $defaultOrder;
	
	const GENERIC_ERROR	= 0;
	const QUERY_FAILED	= 1;
	const INSERT_FAILED	= 2;
	const UPDATE_FAILED	= 3;
	const DELETE_FAILED	= 4;
	
	protected $_getChildrenCallLevel = 0;
	
	public function __construct($db, $table, $pkey, $defaultSort=null) {
		$this->db = $db;
		$this->table = $table;
		$this->pkey = $pkey;
		
		if(!empty($defaultSort)) {
			$this->defaultOrder = $defaultSort;
		}
	}
	
	
	public function log($module, $message, $stacktrace=null) {
		if(is_object($module)) {
			$module = get_class($module);
		}

		try {
			$fields = array(
				'username'		=> '(Anonymous)',
				'admin_id'		=> 0,
				'module'		=> $module,
				'message'		=> $message,
				'ipaddress'		=> $_SERVER['REMOTE_ADDR'],
				'stacktrace'	=> $stacktrace,
			);

			if(isset($_SESSION['MM_Username'])) {
				$fields['username'] = $_SESSION['MM_Username'];
			}
			if(isset($_SESSION['MM_UserID'])) {
				$fields['admin_id'] = $_SESSION['MM_UserID'];
			}

			if(empty($stacktrace)) {
				$bt = new Exception();
				$fields['stacktrace'] = $bt->getTraceAsString();
			}
			debugPrint($fields, __METHOD__ ." - fields");

			$sql = "INSERT INTO log (username, admin_id, module, message, ipaddress, stacktrace) "
					. "VALUES (:username, :admin_id, :module, :message, :ipaddress, :stacktrace)";

			$logResult = $this->db->run_insert($sql, $fields);
		} catch (Exception $ex) {
			$logResult = $ex->getMessage();
		}

		return $logResult;
		
	}
	
	
	public function addTags($id, array $tags) {
		$result = 0;
		
		foreach($tags as $tag) {
			$tmpResult = $this->addTag($id, $tag);
			
			if(is_numeric($tmpResult) && $tmpResult > 0) {
				$result++;
			}
		}
		
		return $result;
	}
	
	
	public static function debugPrint($data, $title=null, $printIt=null) {
		$debug = \debug_backtrace();
		$callingMethod = $debug[1]['class'] .'::'. $debug[1]['function'];
		if(is_null($title)) {
			$title = $callingMethod;
		}
		else {
			$title = $callingMethod ." - ". $title;
		}
		return \debugPrint($data, $title, $printIt);
	}
	
	
	public function update(array $data, $whereId) {
		if(empty($whereId) || intval($whereId) == 0) {
			throw new InvalidArgumentException("missing where statement");
		}
		if(empty($this->table)) {
			throw new Exception("no table defined");
		}
		if(!is_array($data) || empty($data)) {
			throw new \InvalidArgumentException("no data for update");
		}
		
		if(isset($data[$this->pkey])) {
			unset($data[$this->pkey]);
		}
		$q = "UPDATE `$this->table` SET ";
		
		$this->debugPrint($data, "Data");
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

		$q = rtrim($q, ', ') . ' WHERE ' . $this->pkey ." = :pkeyid";
		$params['pkeyid'] = $whereId;
		$this->debugPrint($q, "sql");
		$this->debugPrint($params, "parameters");
		
		return  $this->db->run_update($q, $params);
	}
	
	
	
	public function insert(array $data) {
		if(empty($this->table)) {
			throw new Exception("no table defined");
		}
		$sql = "INSERT INTO `". $this->table ."` ";
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

		$sql .= "(" . rtrim($n, ', ') . ") VALUES (" . rtrim($v, ', ') . ");";
		
		return $this->db->run_insert($sql, $params);
	}
	
	
	public function delete($id) {
		if(empty($id) || !is_numeric($id) || $id < 1) {
			throw new InvalidArgumentException("invalid ID (". $id .")");
		}
		
		$existingInfo = $this->get($id);
		
		if(is_array($existingInfo) && count($existingInfo)) {
			if(intval($existingInfo['media_id']) > 0) {
				try {
					$mediaObj = new media($this->db);
					$mediaObj->delete($existingInfo['media_id']);
				}
				catch(Exception $ex) {
					// Failed to delete attached media item... oh well.
				}
			}

			$sql = "DELETE FROM ". $this->table ." WHERE ". $this->pkey ." = :id";
			$params = array(
				'id'=>$id
			);
			$this->debugPrint($sql, "SQL");
			$this->debugPrint($params, "Parameters for SQL");
		}
		return $this->db->run_update($sql, $params);
	}
	
	
	public function deleteWhere(array $constraints) {
		if(!is_array($constraints) || empty($constraints)) {
			throw new InvalidArgumentException("empty contraints");
		}
		$sql = "DELETE FROM ". $this->table ." WHERE";
		$params = array();
		foreach($constraints as $k=>$v) {
			$sql .= " ". $k ." = :". $k;
			$params[$k] = $v;
		}
		$this->debugPrint($sql, "SQL");
		$this->debugPrint($params, "Parameters for SQL");
		
		return $this->db->run_update($sql, $params);
//		return null;
	}
	
	
	/**
	 * Requires that the $allData array is indexed by the primary key (e.g. 
	 *	using Database::fetch_array_assoc($sql,'page_id') for pages).
	 * 
	 * @param int $id			index to find.
	 * @param array $allData	array to search through for parent_id
	 * @return int				Value of $allData[$id]['parent_id']
	 */
	public static function getParent($id, array &$allData) {
		$parentId = null;
		
		if(isset($allData[$id]) && isset($allData[$id]['parent_id'])) {
			$parentId = $allData[$id]['parent_id'];
		}
		
		return $parentId;
	}
	
	
	public function getParents($id, array $allData) {
		$allParents = array();
		if($id > 0) {
			$i = 0;
			$lastId=$id;
			do {
				if(isset($allData[$lastId])) {
					if(is_numeric($allData[$lastId]['parent_id'])) {
						$lastId = $allData[$lastId]['parent_id'];
					}
					else {
						$lastId = 0;
					}
					$allParents[] = $lastId;
				}
				else {
					$this->debugPrint($allData, __METHOD__ ." - data, looking for (". $id .")");
					throw new LogicException(__METHOD__ .": could not find index ". $lastId .", loop #". $i ."... supplied id=(". func_get_arg(0) .")... ". print_r($allParents, true));
				}
				$i++;
			}
			while($i<count($allData) && $lastId != 0);
		}
		
		return $allParents;
	}
	
	
	/**
	 * Retrieves all child records (passes array by reference to save memory)
	 * 
	 * @param int $parentId		ID to find children for.
	 * @param array $allData	Search this for children
	 * @param int $depth		How far down the rabit hole to go (0 goes forever)
	 * @return array			List of id's that are children of $parentId
	 */
	public function getChildren($parentId, &$allData, $depth=1) {
		if(is_null($this->_getChildrenCallLevel)) {
			$this->_getChildrenCallLevel = 0;
		}
		$this->_getChildrenCallLevel++;
		
		//make sure we haven't gone crazy.
		if($this->_getChildrenCallLevel > count($allData)) {
			throw new LogicException(__METHOD__ .": deep recursion ({$this->_getChildrenCallLevel} > ". count($allData) .")");
		}
		
		$children = array();
		foreach($allData as $idx=>$record) {
			if($record['parent_id'] == $parentId) {
				$children[] = $idx;
			}
		}
		
		if(count($children) && ($this->_getChildrenCallLevel < $depth || $depth < 0)) {
			foreach($children as $childId) {
				$this->getChildren($childId, $allData);
			}
		}
		
		$this->_getChildrenCallLevel--;
		
		//return the call level back to null, if applicable.
		if($this->_getChildrenCallLevel == 0) {
			$this->_getChildrenCallLevel = null;
		}
		return $children;
	}
	
	
	public function get($id, array $constraints = null, $sql=null) {
		if($sql==null || !strlen($sql)) {
			$sql = "SELECT * FROM ". $this->table ." WHERE ". $this->pkey ."=:id";
		}
		$params = array(
			'id'	=> $id,
		);
		$this->debugPrint($sql, "sql before adding additional items");
		
		if(is_array($constraints)) {
			foreach($constraints as $k=>$v) {
				if(is_object($v) && preg_match('~constraint$~', get_class($v)) == 1) {
					$sql = ToolBox::create_list($sql, $k .' '. $v->render(), " AND ");
				}
				else {
					$sql = ToolBox::create_list($sql, "{$k}=:{$k}", " AND ");
					$params[$k] = $v;
				}
			}
		}
		
		$this->debugPrint($sql, "SQL");
		$this->debugPrint($params, "Parameters");
		
//			exit(__FILE__ ." - line #". __LINE__);
		$this->db->run_query($sql, $params);
		
		return $this->db->get_single_record();
	}
	
	
	
	public function getAll($orderBy=null, array $constraints=null, $sql=null) {
		$this->debugPrint(func_get_args(), "Arguments");
		if($sql == null || empty($sql)) {
			$sql = "SELECT * FROM ". $this->table;
		}
		
		$params = array();
		
		if(is_array($constraints) && count($constraints)) {
			$constraintSql = '';
			foreach($constraints as $k=>$v) {
				if(is_object($v) && preg_match('~constraint$~', get_class($v)) == 1) {
					$constraintSql = ToolBox::create_list($constraintSql, $k .' '. $v->render(), " AND ");
			$this->debugPrint($constraintSql, "sql after adding a constraint object");
				}
				else {
					$paramKey = preg_replace('~\.~', '_', $k);
					$constraintSql = ToolBox::create_list($constraintSql, "{$k} LIKE :{$paramKey}", " AND ");
					$params[$paramKey] = $v;
				}
			}
			$sql .= ' WHERE '. $constraintSql;
//			exit;
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
		
		// TODO: have an option/argument/something to make this call Database::farray() instead (to allow duplicates)
		return $this->db->farray_fieldnames($this->pkey);
	}
	
	
	protected function getAll_nvp($valueField, $orderBy=null, array $constraints=null, $sql=null) {
		if(empty($valueField)) {
			throw new InvalidArgumentException();
		}
		else {
			try {
				$allData = $this->getAll($orderBy, $constraints, $sql);

			} catch (Exception $ex) {
				// should this... do something different?  fthrow the exception along.
				throw $ex;
			}
			$nvp = array();
			foreach($allData as $k=>$v) {
				if(isset($v[$valueField])) {
					$nvp[$k] = $v[$valueField];
				}
				else {
					throw new Exception("Value field missing from data (". $valueField .")");
				}
			}
			
			return $nvp;
		}
	}
	
	
	public final function simpleGetAll() {
		$sql = "SELECT * FROM ". $this->table;
		$params = array();
		$this->db->run_query($sql, $params);
		return $this->db->farray_fieldnames();
	}
	
	public final function simpleGet($id) {
		$sql = "SELECT * FROM ". $this->table ." WHERE ". $this->pkey ."=:id";
		$params = array(
			'id'	=> $id,
		);
		$this->db->run_query($sql, $params);
		return $this->db->get_single_record();
	}
}
