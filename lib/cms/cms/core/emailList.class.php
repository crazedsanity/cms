<?php

/*
 * Code for handling email lists.  Adds appropriate records for linking, etc.
 */

namespace cms\cms\core;

/**
 * Description of emailList
 *
 * @author danf
 */
class emailList extends core {
	
	public function __construct($db) {
		parent::__construct($db, 'email_lists', 'email_list_id');
	}
	
	
	public function getAllListNames() {
		$sql = "SELECT * FROM email_list_names ORDER BY email_list_name";
		
		$this->db->run_query($sql);
		
		return($this->db->farray_fieldnames());
	}
	
	
	public function getAllListNames_nvp() {
		$sql = "SELECT * FROM email_list_names ORDER BY email_list_name";
		$this->db->run_query($sql);
		return $this->db->farray_nvp('email_list_name_id', 'email_list_name');
	}
	
	
	public function getListName($name, $autoCreate=false) {
		$sql = "SELECT * FROM email_list_names WHERE email_list_name=:name";
		$params = array('name'=>$name);
		
		$num = $this->db->run_query($sql, $params);
		
		
		if($num === 1) {
			$retval = $this->db->get_single_record();
		}
		else {
			$this->db->run_insert("INSERT INTO email_list_names (email_list_name) VALUES (:name)", $params);
			if($this->db->run_query($sql, $params) > 0) {
				$retval = $this->db->get_single_record();
			}
			else {
				throw new \LogicException("failed to auto-create record");
			}
		}
		
		return $retval;
	}
	
	
	public function getAll($orderBy = null, array $constraints = null, $expandExtraColumns=true) {
		$allTheData = parent::getAll($orderBy, $constraints);
		
		$extraColumns = array();
		if($expandExtraColumns === true) {
			// process the data a bit.
			foreach($allTheData as $k=>$v) {
				if(!empty($v['extra_data'])) {
					$xDecode = json_decode($v['extra_data']);
					foreach($xDecode as $xName=>$xVal) {
						// prefix the name with something to avoid accidental overlaps.
						$theIndex = 'x_'. $xName;

						// show a pretty name for the column heading
						$prettyName = ucwords(preg_replace('/_/', ' ', $xName));
						$extraColumns[$theIndex] = $prettyName;

						if(preg_match('/^yes_/', $xName) == 1) {
							$showThis = '';
							if(intval($xVal) == 1) {
								$showThis = 'yes';
							}
							$xVal = $showThis;
						}
						$allTheData[$k][$theIndex] = $xVal;
					}
					unset($allTheData[$k]['extra_data']);
				}
			}
		}
		
		return $allTheData;
	}
	
	
	public function getRandom($listId) {
		$sql = "SELECT * FROM email_lists WHERE email_list_name_id=:name ORDER BY RAND() LIMIT 1";
		$params = array('name'=>$listId);
		$this->db->run_query($sql, $params);
		return $this->db->get_single_record();
	}
	
	
	public function add(array $data, $listNameOrId, array $extraData=null) {
		$theInsert = $data;
		if(intval($listNameOrId) > 0) {
			$theInsert['email_list_name_id'] = $listNameOrId;
		}
		else {
			$theList = $this->getListName($listNameOrId, true);
			$theInsert['email_list_name_id'] = $theList[$this->pkey];
		}
		
		if(is_array($extraData) && count($extraData) > 0) {
			$theInsert['extra_data'] = json_encode($extraData);
		}
		
		return $this->insert($theInsert);
	}
	
	
}
