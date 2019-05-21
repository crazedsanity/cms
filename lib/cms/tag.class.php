<?php

namespace cms;


class tag extends \cms\cms\core\core {
	protected $db;
	
	
	public function __construct(\Database $db) {
		$this->db = $db;
		parent::__construct($db, 'tags', 'tag_id', 'tag_name');
	}
	
	
	public function get($id, array $constraints = null) {
		
		$sql = "
			SELECT
				 t.*
				,tt.tag_type_id
				,tt.tag_type_name
			FROM tags AS t
				INNER JOIN tag_types AS tt ON (t.tag_type_id=tt.tag_type_id)
				WHERE tag_id=:id";
		
		return parent::get($id, $constraints, $sql);
		
//		parent::get($id, $constraints);
		
		
	}
	
	
	public function getAll($orderBy=null, $constraints=null) {
		$sql = "
			SELECT
				 t.*
				,tt.tag_type_id
				,tt.tag_type_name
			FROM tags AS t
				INNER JOIN tag_types AS tt ON (t.tag_type_id=tt.tag_type_id)";
		
		if(is_null($orderBy)) {
			$orderBy = 'tt.tag_type_name, t.tag_name';
		}
		
		return parent::getAll($orderBy, $constraints, $sql);
	}
}
