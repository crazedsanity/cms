<?php

namespace cms\cms\employment;

use crazedsanity\core\ToolBox;
use cms\cms\core\core;
use cms\database\constraint;


class employment extends core {
	
	protected $db;
	
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'employment', 'employment_id');
		$this->db = $db;
	}
	
	public function update(array $data, $whereId) {
		if(isset($data['end_date']) && empty($data['end_date'])) {
			$data['end_date'] =  null;
		}
		if(isset($data['public_date']) && empty($data['public_date'])) {
			$data['public_date'] =  null;
		}
		return parent::update($data, $whereId);
	}
	
	
	public function insert(array $data) {
		if(isset($data['end_date']) && empty($data['end_date'])) {
			$data['end_date'] =  null;
		}
		if(isset($data['public_date']) && empty($data['public_date'])) {
			$data['public_date'] =  null;
		}
		return parent::insert($data);
	}
	
	
	public function get_activePublic($id) {
		
		$this->debugPrint(func_get_args(), "arguments");
		
		$constraints = null;
		$sql = "
			SELECT * FROM ". $this->table ." WHERE 
				start_date <= current_date() 
				AND (end_date >= current_date() OR end_date IS NULL)
				AND (public_date <= current_date OR public_date IS NULL)
				AND is_active=1 AND employment_id=:id";
		return parent::get($id, $constraints, $sql);
	}
	
	public function get_activeInternal($id) {
		$this->debugPrint(func_get_args(), "arguments");
		
		$constraints = null;
		$sql = "
			SELECT * FROM ". $this->table ." WHERE 
				start_date <= current_date() 
				AND (end_date >= current_date() OR end_date IS NULL)
				AND is_active=1 
				AND employment_id=:id";
		
		return parent::get($id, $constraints, $sql);
	}
	
	public function getAll_activePublic() {
		
		$this->debugPrint(func_get_args(), "arguments");
		
		$constraints = null;
		$sql = "
			SELECT * FROM ". $this->table ." WHERE 
				start_date <= current_date() 
				AND (end_date >= current_date() OR end_date IS NULL)
				AND public_date <= current_date
				AND is_active=1";
		return parent::getAll(null, null, $sql);
		
	}
	public function getAll_activeInternal() {
		$this->debugPrint(func_get_args(), "arguments");
		
		$constraints = null;
		$sql = "
			SELECT * FROM ". $this->table ." WHERE 
				start_date <= current_date() 
				AND (end_date >= current_date() OR end_date IS NULL)
				AND is_active=1 ";
		
		return parent::getAll(null, null, $sql);
		
	}
}
