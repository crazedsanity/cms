<?php

namespace cms;

class category extends \cms\cms\core\core {
	protected $db;
	
	
	public function __construct(\crazedsanity\database\Database $db, $table, $pkey) {
		parent::__construct($db, 'categories', 'category_id', 'category_name');
	}
	
	
	
}
