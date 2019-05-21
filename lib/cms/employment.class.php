<?php

namespace cms;

use crazedsanity\core\ToolBox;
use \Exception;
use \InvalidArgumentException;

class employment extends core {
	
	protected $db;
	
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'employment', 'employment_id');
		$this->db = $db;
	}
	
	
	/**
	 * Retrieves all records, defaulting to only active ones with an optional limit.
	 * 
	 * @param bool $onlyActive	Show only records with is_active=1
	 * @param type $limit		Limit the number of results returned (defaults to unlimited)
	 * @return array			The records.
	 */
	public function getAll($onlyActive=true, $limit=null) {
		$params = array();
		$activeSql = "";
		$limitSql = "";
		if($onlyActive === true) {
			$activeSql = "WHERE is_active=1";
		}
		if(!is_null($limit) && is_numeric($limit) && $limit > 0) {
			$limitSql = "LIMIT $limit";
		}
		$sql = "
			SELECT
				 e.*
			FROM
				employment AS e
			{$activeSql}
			ORDER BY e.title
			{$limitSql}
			";
		$this->debugPrint($sql, "SQL");
		$this->db->run_query($sql, $params);
		
		return $this->db->farray_fieldnames($this->pkey);
	}
	
	
	
	/**
	 * Retrieves the given employment record and media information.
	 * 
	 * @param	int	$id		The record to retrieve.
	 * @return	array		Data for that record (returns an empty array if none found)
	 */
	public function get($id, $onlyActive=true) {
		$sql = "
			SELECT
				 e.*
			FROM
				employment AS e
			WHERE e.employment_id=:id
			";
		$params = array(
			'id'	=> $id
		);
		if($onlyActive == true) {
			$sql .= " AND is_active=1";
		}
		$this->debugPrint($sql, "sql");
		
		$this->db->run_query($sql, $params);
		
		return $this->db->get_single_record();
	}
	
	
	/**
	 * Deletes the record.
	 * 
	 * @param	int	$id		The employment_id record to delete
	 * @return	int			How many records were affected.
	 */
	public function delete($id) {
		$info = $this->get($id);
		$res = parent::delete($id);
		return($res);
	}
}
