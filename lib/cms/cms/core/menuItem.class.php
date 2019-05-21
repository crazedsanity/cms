<?php

namespace cms\cms\core;

class menuItem extends core {
	protected $db;
	
	public function __construct(Database $db) {
		$this->db = $db;
		parent::__construct($db, 'menu_items', 'menu_item_id');
	}
}
