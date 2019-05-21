<?php

namespace cms;

use cms\tag;

class mediaTag extends \cms\cms\core\core {
	protected $db;
	
	
	public function __construct(\crazedsanity\database\Database $db) {
		parent::__construct($db, 'media_tags', 'media_tag_id');
	}
	
	
	public function getAll($orderBy = null, array $constraints = null) {
//		throw new \BadMethodCallException(__METHOD__ ." - needs to be re-implemented");
		$sql = "
			SELECT
				 mt.media_tag_id
				,m.media_id
				,m.display_filename
				,m.filesize
				,m.media_folder_id
				,mf.path
				,mf.display_name AS folder_display_name
				,t.tag_id
				,t.tag_name
				,t.tag_type_id
				,tt.tag_type_name
			FROM 
				media AS m
				INNER JOIN media_folders AS mf ON (m.media_folder_id=mf.media_folder_id)
				INNER JOIN media_tags AS mt ON (m.media_id=mt.media_id)
				INNER JOIN tags AS t ON (mt.tag_id=t.tag_id)
				INNER JOIN tag_types AS tt ON (t.tag_type_id=tt.tag_type_id)
				";
		
		$data = parent::getAll($orderBy, $constraints, $sql);
		$this->debugPrint($data, "returning data");
		
		
		return $data;
		
	}
	
	
	public function getAll_sorted($orderBy = null, array $constraints = null) {
		$data = $this->getAll($orderBy, $constraints);
		
		$retval = array();
		foreach($data as $k=>$v) {
			if(!isset($retval[$v['tag_type_id']])) {
				$retval[$v['tag_type_id']] = array();
			}
			$retval[$v['tag_type_id']][$v['tag_id']] = $v['tag_name'];
		}
		
		return $retval;
	}
	
	
	
	public function addTagList($mediaId, $tagTypeId, $tagList) {
		$this->debugPrint(func_get_args(), "arguments");
		
		//TODO: make the format of the "info" array something useable when creating an alert for the user.
		$info = array();
		
			$tags = array();
			
		if(strlen($tagList) > 0) {
			$tags = explode(',', $tagList);
		}
		$this->debugPrint($tags, "tag-splosion");

		$queryCriteria = array(
			'm.media_id'=>$mediaId,
			't.tag_type_id'=>$tagTypeId
		);

		$allTags = $this->getAll_nvp('tag_name', $queryCriteria);
		$this->debugPrint($allTags, "All existing tags (typeId=". $tagTypeId .") for this record");


		// TODO: avoid unnecessary deletions (deleting a tag destined to be added later)

		// if there are any tags, delete 'em.
		if(count($allTags) > 0) {
			$info['deleted'] = array();
			foreach($allTags as $k=>$v) {
				$delRes = $this->delete($k);
				$info['deleted'][] = $v;
			}
		}

		// next, add tags.
		$_tags = new tag($this->db);
		foreach($tags as $name) {
			// is it a valid tag?
			$tagInfo = $_tags->getAll(null, array('tag_name'=>$name));
			debugPrint($tagInfo, "data found for tag (". $name .")");

			if(is_array($tagInfo) && count($tagInfo) == 1) {
				// FOUND ONE!!!
				$keys = array_keys($tagInfo);
				$realTagInfo = $tagInfo[$keys[0]];
				try {
				$addParams = array(
					'media_id'	=> $mediaId,
					'tag_id'	=> $realTagInfo['tag_id']
				);
				$this->debugPrint($addParams, "parameters for adding a tag");
				$addRes = $this->insert($addParams);
				$this->debugPrint($addRes, "result of adding tag");

//					$info['added']
				if(!isset($info['added'])) {
					$info['added'] = array();
				}
				$info['added'][] = $name;
				}
				catch(Exception $ex) {
					$this->debugPrint($ex->getMessage, "FUCK");
				}
			}
			else {
				if(!isset($info['invalid'])) {
					$info['invalid'] = array();
				}
				$info['invalid'][] = $name;
			}
		}
		
		$this->debugPrint($info, "returning info");
		return $info;
	}
	
	
	public function getAll_nvp($orderBy=null, array $constraints=null, $sql=null) {
		$this->debugPrint(func_get_args(), "arguments");
		return parent::getAll_nvp('tag_name', $orderBy, $constraints, $sql);
	}
}
