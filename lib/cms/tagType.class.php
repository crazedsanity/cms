<?php


namespace cms;

class tagType extends \cms\cms\core\core {
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'tag_types', 'tag_type_id');
	}
	
	
	public function getAll_nvp($orderBy = null, array $constraints = null) {
		return parent::getAll_nvp('tag_type_name', $orderBy, $constraints);
	}
}
