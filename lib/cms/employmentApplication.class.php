<?php

namespace cms;

use crazedsanity\core\ToolBox;
use \Exception;
use \InvalidArgumentException;

class employmentApplication extends core {
	
	protected $db;
	
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'applications', 'application_id');
		$this->db = $db;
	}
	
	
	public function getAll($limit=null) {
		$params = array();
		$activeSql = "";
		$limitSql = "";
		if(!is_null($limit) && is_numeric($limit) && $limit > 0) {
			$limitSql = "LIMIT $limit";
		}
		$sql = "
			SELECT
				 a.*
			FROM
				applications AS a
			{$activeSql}
			ORDER BY a.created
			{$limitSql}
			";
		$this->debugPrint($sql, "SQL");
		$this->db->run_query($sql, $params);
		
		return $this->db->farray_fieldnames($this->pkey);
	}
	
	
	
	public function get($id) {
		$sql = "
			SELECT
				 a.*
			FROM
				applications AS a
			WHERE a.application_id=:id
			";
		$params = array(
			'id'	=> $id
		);
		$this->debugPrint($sql, "sql");
		
		$this->db->run_query($sql, $params);
		
		return $this->db->get_single_record();
	}
	
	
	public function delete($id) {
		$info = $this->get($id);
		$res = parent::delete($id);
		return($res);
	}
}
