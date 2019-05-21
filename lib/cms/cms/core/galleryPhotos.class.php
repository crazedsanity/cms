<?php

namespace cms\cms\core;

use crazedsanity\core\ToolBox;
use \Exception;
use \InvalidArgumentException;

class galleryPhotos extends core {
	
	protected $db;
	public $galleryId;
	
	public function __construct(\crazedsanity\database\Database $db, $galleryId=null) {
		parent::__construct($db, 'gallery_photos', 'gallery_photo_id');
		$this->db = $db;
		$this->galleryId = $galleryId;
	}
	
	
	
	public function getAll() {
		$sql = "
		SELECT 
			 gp.* 
			,gp.name AS photo_name
			,g.name AS gallery_name
			,g.description AS gallery_description
			,m.filename
		FROM 
			gallery_photos AS gp
			LEFT OUTER JOIN galleries AS g ON (gp.gallery_id=g.gallery_id)
			LEFT OUTER JOIN media AS m ON (gp.media_id=m.media_id)";
		
		if(intval($this->galleryId) > 0) {
		$sql .= " WHERE
			gp.gallery_id=:gid";
			$params = array(
				'gid'	=> $this->galleryId,
			);
		}
		$sql .= " ORDER BY name";
		
		$this->db->run_query($sql, $params);
		
		return $this->db->farray_fieldnames('gallery_photo_id');
	}
	
	
	
	public function create(array $data) {
		if(!isset($data['name'])) {
			throw new InvalidArgumentException("missing name");
		}
		
		$data['gallery_id'] = $this->galleryId;
		
		$newId = $this->insert('gallery_photos', $data);
		
		return $newId;
	}
	
	
	public function delete($id) {
		
		
		$data = $this->get($id);
		$this->debugPrint($data, "data");
		
		if(!empty($data) && intval($data['media_id']) > 0) {
			// delete the media record.
			$mObj = new media($this->db);
			$delRes = $mObj->delete($data['media_id']);
		}
		return parent::delete($id);
	}
	
	
	public function getMediaFolderId() {
		$mObj = new mediaFolder($this->db);
		$sObj = new settings($this->db);
		
		$settingName = 'gallery_media_folder_id';
		$displayName = 'Gallery Photos';
		
		$theId = $sObj->get($settingName, 'internal');
		$this->debugPrint($theId, "ID");
		
		if(!intval($theId)) {
			// is there an existing folder?
			$data = $mObj->getAll(null, array(
				'display_name'	=> $displayName,
			));
			$this->debugPrint($data, "existing data found");
			if(is_array($data) && count($data) == 1) {
				$theId = array_keys($data)[0];
				$this->debugPrint($theId, "the existing data");
			}
			else {
				// No ID.  Create it.
				$theId = $mObj->insert(array(
					'path'			=> PUBLIC_MEDIA_DIR,
					'display_name'	=> 'Gallery Photos',
				));
			}
			$this->debugPrint($theId, "new ID for setting");
			$sObj->set($theId, $settingName);
		}
		
		return $theId;
	}
}
