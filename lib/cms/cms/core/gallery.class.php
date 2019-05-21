<?php

namespace cms\cms\core;

use crazedsanity\core\ToolBox;
use \Exception;
use \InvalidArgumentException;

class gallery extends core {
	
	protected $db;
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'galleries', 'gallery_id');
		$this->db = $db;
	}
	
	
	
	public function getAll() {
		$sql = "
		SELECT 
			 g.* 
			,m.filename
		FROM 
			galleries AS g
			LEFT OUTER JOIN media AS m ON (g.media_id=m.media_id)
		ORDER BY name";
		
		$params = array();
		$this->db->run_query($sql, $params);
		
		return $this->db->farray_fieldnames($this->pkey);
	}
	
	
	
	public function create(array $data) {
		if(!isset($data['name'])) {
			throw new InvalidArgumentException("missing name");
		}
		if(!isset($data['gallery_type_id']) || !is_numeric($data['gallery_type_id'])) {
			throw new InvalidArgumentException("missing type");
		}
		
		$newId = $this->insert('galleries', $data);
		
		return $newId;
	}
	
	
	public function update($galleryId, array $data) {
		if(!isset($galleryId) || !is_numeric($galleryId) || $galleryId <= 0) {
			throw new InvalidArgumentException("invalid gallery ID");
		}
		
		$result = parent::update($data, $galleryId);
	}
	
	
	public function getAll_nvp($orderBy = null, array $constraints = null, $sql = null) {
		$valueField = 'name';
		return parent::getAll_nvp($valueField, $orderBy, $constraints, $sql);
	}
}
