<?php

namespace cms\cms\core;

use \crazedsanity\core\ToolBox;
use \Exception;

class notification extends core {
	protected $db;
	
	const GENERIC_EXCEPTION = 1;
	const INSERT_FAILED = 2;
	const QUERY_FAILED = 3;
	const INVALID_TYPE = 4;
	const UPDATE_FAILED = 5;
	
	public function __construct($db) {
		$this->db = $db;
	}
	
	
	public function create_type($id, $type, $description, $hexColor) {
		$sql = 'INSERT INTO notification_types (notification_id, notification_type, 
			description, color) VALUES 
			(:id, :type, :desc, :color)';
		
		$params = array(
			'id'	=> $id,
			'type'	=> $type,
			'desc'	=> $description,
			'color'	=> $hexColor,
		);
		
		try {
			$result = $this->db->run_insert($sql, $params, null);
		} catch (Exception $ex) {
			throw new Exception('failed to create record', self::INSERT_FAILED, $ex);
		}
		
		return $result;
	}
	
	
	public function getTypes() {
		$sql = 'SELECT * FROM notification_types ORDER BY notification_type_id';

		try {
			$numRecords = $this->db->run_query($sql);
			$records = $this->db->farray_fieldnames('notification_type_id');
		} catch (Exception $ex) {
			throw new exception('failed to retrieve records', self::QUERY_FAILED, $ex);
		}
		
		return $records;
	}
	
	
	public function getTypesForOptionList() {
		$allRecords = $this->getTypes();
		
		$records = array();
		foreach($allRecords as $id=>$data) {
			$records[$id] = ucfirst($data['notification_type']) ." - ". $data['description'];
		}
		
		return $records;
	}
	
	
	public function create($title, $body, $type=1) {
		$types = $this->getTypes();
		if(isset($types[$type])) {
			$sql = 'INSERT INTO notifications (title, body, notification_type_id, date_created) VALUES 
				(:title, :body, :type, :date)';
			$params = array(
				'title'	=> $title,
				'body'	=> $body,
				'type'	=> $type,
				'date'	=> date("Y-m-d H:i:s"),
			);
			
			try {
				$newId = $this->db->run_insert($sql, $params, null);
			} catch (Exception $ex) {
				throw new Exception('failed to create record: '. $ex->getMessage(), self::INSERT_FAILED, $ex);
			}
		}
		else {
			throw new Exception('invalid type: '. print_r($types,true), self::INVALID_TYPE);
		}
		
		return $newId;
	}
	
	
	public function deactivate($id) {
		$result = $this->update($id, array('is_active'=>0));
		
		return $result;
	}
	
	
	public function delete($id) {
		$sql = 'DELETE FROM notifications WHERE notification_id=:id';
		$params = array(
			'id'	=> $id,
		);
		
		try {
			$result = $this->db->run_update($sql, $params);
		} catch (Exception $ex) {
			throw new Exception('unable to delete record', self::QUERY_FAILED, $ex);
		}
		
		return $result;
	}
	
	
	public function update(array $fieldToValue, $id) {
		//build the update string.
		$updates = "";
		$params = array();
		if(isset($fieldToValue['notification_id'])) {
			unset($fieldToValue['notification_id']);
		}
		foreach($fieldToValue as $field => $value) {
			$updates = ToolBox::create_list($updates, $field .'=:'. $field, ", ");
			$params[$field] = $value;
		}
		
		$sql = 'UPDATE notifications SET '. $updates .' WHERE notification_id=:id';
		$params['id'] = $id;
		
		try {
			$result = $this->db->run_update($sql, $params);
		} catch (PDOException $ex) {
			\debugPrint($ex, __METHOD__ ." - failed to run update");
			throw new Exception('failed to update record', self::UPDATE_FAILED, $ex);
		}
		
		return $result;
	}
	
	
	public function get($id, array $constraints=null, $sql=null) {
		$sql = 'SELECT 
			 n.*
			,t.*
		FROM
			notifications AS n
			INNER JOIN notification_types as t ON (n.notification_type_id=t.notification_type_id)
			WHERE n.notification_id=:id';
		$record = parent::get($id, $constraints, $sql);
		
		
		return $record;
	}
	
	
	public function getAll($orderBy=NULL, array $constraings=NULL, $sql=NULL) {
		$sql = 'SELECT 
				 n.*
				,t.*
			FROM
				notifications AS n
				INNER JOIN notification_types as t ON (n.notification_type_id=t.notification_type_id)
				ORDER BY is_active, title, notification_id';
		
		$records = parent::getAll($orderBy, $constraings, $sql);
		
		return $records;
	}
	
	
	public function getMostRecent() {
		$sql = 'SELECT 
			 n.*
			,t.*
		FROM
			notifications AS n
			INNER JOIN notification_types as t ON (n.notification_type_id=t.notification_type_id)
		WHERE
			n.is_active=1
		ORDER BY t.notification_type_id DESC, date_created DESC LIMIT 1';
		
		try {
			$this->db->run_query($sql);
			$record = $this->db->get_single_record();
		} catch (Exception $ex) {
			throw new Exception('unable to retrieve most recent record', self::QUERY_FAILED, $ex);
		}
		
		return $record;
	}
}
