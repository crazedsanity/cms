<?php

namespace cms\cms\core;

class menu extends core {
	protected $db;
	
	public function __construct($db) {
		$this->db = $db;
		parent::__construct($db, 'menus', 'menu_id');
	}
	
	
	public function getAll() {
		$sql = "
			SELECT
				mi.menu_item_id
				,mi.title
				,mi.parent_id
				,mi.sub_menu_id
				,m.name
				,m.menu_id
				,(SELECT group_concat(x.menu_item_id SEPARATOR ',') from menu_items as x WHERE x.parent_id=mi.menu_item_id) as _children
				,(SELECT count(*) FROM menu_items as mi2 WHERE mi2.parent_id=mi.menu_item_id) as _num_children
			FROM
				menu_items as mi
				right join menus as m on m.menu_id=mi.menu_id
			ORDER BY 
				m.menu_id, mi.sort";
		return $this->db->fetch_array($sql);
	}
	
	
	public function getAll_menus() {
		
		$sql = "
			SELECT
				m.*
			FROM
				menus as m
			ORDER BY 
				m.menu_id";
		return $this->db->fetch_array($sql);
	}
	public function deleteMenu($menuId) {
		$result = null;
		if(is_numeric($menuId)) {
			$sql = "DELETE FROM menus WHERE menu_id={$menuId}";
			$this->db->query($sql);
			$result = $this->db->affected_rows;
		}
		
		return $result;
	}
	
	
	public function deleteMenuItem($menuItemId) {
		$result = null;
		if(is_numeric($menuId)) {
			$sql = "DELETE FROM menu_items WHERE menu_item_id={$menuItemId}";
			$this->db->query($sql);
			$result = $this->db->affected_rows;
		}
		
		return $result;
	}
}
