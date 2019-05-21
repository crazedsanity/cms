<?php
namespace cms\database;


use InvalidArgumentException;
use Exception;
use \crazedsanity\core\ToolBox;

/*
 * Builds a bulk insert statement, like:
 * 
 * INSERT INTO tablename (name,value) VALUES ('first', '1'),('second','2');
 * 
 * Used to make mass-inserts much faster (think 1 vs 1000).
 * 
 * TODO: doInsert() should reset things, so a single instance can handle multiple inserts
 * (useful if the best performance happens at <= 5k records, and there's 100k records
 * to insert).
 */


/**
 * Description of bulkInsert
 *
 * @author danf
 */
class bulkInsert {
	
	protected $tableName;
	protected $columns = array();
	protected $columnDefaults = array();

	
	private $_data = array();
	
	/**
	 * 
	 * @param type $tableName
	 * @param array $columns
	 * @throws InvalidArgumentException
	 */
	public function __construct($tableName, array $columns) {
		$this->tableName = $tableName;
		$this->columns = $columns;
	}
	
	
	public function setDefaults(array $col2val) {
		foreach($col2val as $col => $val) {
			if(in_array($col, $this->columns)) {
				$this->columnDefaults[$col] = $val;
			}
			else {
				throw new InvalidArgumentException("cannot specify column default for unknown column ({$col})");
			}
		}
	}
	
	
	public function add(array $data) {
		$myData = array();
		foreach($data as $k=>$v) {
			if(in_array($k, $this->columns)) {
				$myData[$k] = $v;
			}
			else {
				throw new InvalidArgumentException("invalid column ({$k}) in data");
			}
		}
		
		
		// if there's missing columns, fill them with the specified default value.
		if(count($myData) !== count($this->columns)) {
			foreach($this->columnDefaults as $k=>$v) {
				if(!isset($myData[$k])) {
					$myData[$k] = $v;
				}
			}
		}
		
		$countMyData = count($myData);
		$countThisColumns = count($this->columns);
		if(count($myData) === count($this->columns)) {
			$addThis = array();
			foreach($this->columns as $col) {
				$addThis[$col] = $myData[$col];
			}
			$this->_data[] = $addThis;
		}
		else {
			throw new \LogicException("final column count does not match ({$countMyData} != {$countThisColumns})");
		}
		
		return count($this->_data);
	}
	
	
	
	public function doInsert($db) {
		
		$sql = "INSERT INTO {$this->tableName} (". join(',', $this->columns) .") VALUES ";
		$params = array();
			
		foreach($this->_data as $i=>$insertData) {
			$thisRecord = '';
			foreach($insertData as $field=>$value) {
				$paramName = "{$field}{$i}";
				$params[$paramName] = $value;
				$thisRecord = ToolBox::create_list($thisRecord, ":{$paramName}");
			}
			
			$sql .= "\n\t";
			if($i > 0) {
				$sql .= ",";
			}
			$sql .= "({$thisRecord})";
		}
		
		try {
			$retval = $db->run_query($sql, $params);
		} 
		catch(Exception $ex) {
			throw new Exception("Error while performing mass-insert::: ". $ex->getMessage());
		}
		
		return $retval;
	}
}
