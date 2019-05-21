<?php

namespace cms\cms\core;

use \PDOException;
use \Exception;




class mediaFolder extends core {

	protected $db;
	
	
	public function __construct($db) {
		parent::__construct($db, 'media_folders', 'media_folder_id');
	}
	
}
