<?php

namespace cms\cms\core;

class group extends core {
	
	
	public function __construct($db) {
		$this->db = $db;
		parent::__construct($this->db, 'groups', 'group_id', 'name,group_id');
	}
	
	
	public function getAll_nvp() {
		return parent::getAll_nvp('name');
	}
	
}