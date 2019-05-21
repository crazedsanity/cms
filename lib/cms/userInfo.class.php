<?php

namespace cms;

class userInfo extends core {
	protected $db;
	
	public function __construct(\Database $db) {
		parent::__construct($db, 'admins', 'admin_id');
		$this->db = $db;
	}
	
	
	public function getByAdminId($admin_id) {
		$sql = "SELECT 
				admin_id, 
				name, 
				username, 
				email, 
				created, 
				modified 
			FROM 
				admins 
			WHERE 
				admin_id = :id";
		$params = array(
			'id'	=> $admin_id,
		);
		$this->db->run_query($sql, $params);
		
		return $this->db->get_single_record();
	}
	
	
	public function getByUsername($user) {
		$sql = "SELECT 
				admin_id, 
				name, 
				username, 
				email, 
				created, 
				modified 
			FROM 
				admins 
			WHERE 
				username = :user";
		$params = array(
			'user'	=> $user
		);
		$this->db->run_query($sql, $params);
		$data = $this->db->get_single_record();
		
		return $data;
	}
}
