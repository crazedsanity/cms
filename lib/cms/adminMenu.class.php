<?php

namespace cms;

use crazedsanity\core\ToolBox;
use \Exception;
use \InvalidArgumentException;

class adminMenu extends core {
	
	protected $db;
	
	protected $cache = array();
	
	const CHILDINDEX = '__SUBITEMS__';
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'admin_menus', 'admin_menu_id');
		$this->db = $db;
	}
	
	
	
	public function getAll() {
		if(!is_array($this->cache) || count($this->cache) < 1) {
			
//			$sql = "SELECT * FROM admin_menus WHERE is_active=1 ORDER BY sort, title";
			$sql = "
				SELECT 
					 am.admin_menu_id
					,am.title
					,am.code
					,am.link
					,am.is_active
					,am.show_beneath
					,a.asset_id
					,a.name AS asset_name
					,a.location AS asset_location
					,a.clean_name AS asset_clean_name
				FROM 
					admin_menus AS am
					LEFT OUTER JOIN assets AS a ON (a.asset_id=am.asset_id)
				WHERE am.is_active=1
				ORDER BY am.sort, am.title";
			$params = array();
			$this->db->run_query($sql, $params);

			$this->cache = $this->db->farray_fieldnames('code');
			
			//fix cache to have child items
			foreach($this->cache as $code=>$record) {
				if(!empty($record['show_beneath']) && isset($this->cache[$record['show_beneath']])) {
					$this->cache[$record['show_beneath']][self::CHILDINDEX][$code] = $record;
					unset($this->cache[$code]);
				}
			}
			
		}
		
		return $this->cache;
	}
	
	
	public function getSubItems($forCode) {
		$allData = $this->getAll();
		
		$items = array();
		foreach($allData as $code=>$record) {
			if(!empty($record['show_beneath']) && isset($allData[$record['show_beneath']])) {
				if($record['show_beneath'] == $forCode) {
					$items[$code] = $record;
				}
			}
		}
		
		return $items;
	}
}
