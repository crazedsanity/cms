<?php

namespace cms\cms\core;

use \PDOException;
use \Exception;




class assets extends core {

	protected $db;
	
	
	public function __construct($db) {
		parent::__construct($db, 'assets', 'asset_id');
	}
	
}