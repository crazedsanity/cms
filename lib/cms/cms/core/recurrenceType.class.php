<?php


namespace cms\cms\core;

use \Exception;
use \InvalidArgumentException;

class recurrenceType extends core {
	
	
	public function __construct($db) {
		$this->db = $db;
		parent::__construct($db, 'recurrence_types', 'recurrence_type_id', 'recurrence_type_id');
	}
	
	
	public function getAll_nvp() {
		return parent::getAll_nvp('recurrence_type');
	}
}
