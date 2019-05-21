<?php

namespace cms;

class mediaFolderConstraint extends \cms\cms\core\core {
	
	public function __construct($db) {
		parent::__construct($db, 'media_folder_constraints', 'media_folder_constraint_id', 'media_folder_id');
	}
	
	
	
	public function getAll($orderBy = null, array $constraints = null) {
		$sql = "
			SELECT 
				 mfc.media_folder_constraint_id
				,mf.media_folder_id
				,mf.path
				,mf.display_name
				,mf.actual_filename
				,tt.tag_type_name
				,tt.tag_type_id
			FROM
				media_folder_constraints AS mfc
				INNER JOIN media_folders AS mf ON (mfc.media_folder_id=mf.media_folder_id)
				INNER JOIN tag_types AS tt ON (mfc.tag_type_id=tt.tag_type_id)
				";
		return parent::getAll($orderBy, $constraints, $sql);
	}
	
	public function getAll_nvp($orderBy = null, array $constraints = null) {
		
		$sql = "
			SELECT 
				 mfc.media_folder_constraint_id
				,mf.media_folder_id
				,mf.path
				,mf.display_name
				,mf.actual_filename
				,tt.tag_type_name
			FROM
				media_folder_constraints AS mfc
				INNER JOIN media_folders AS mf ON (mfc.media_folder_id=mf.media_folder_id)
				INNER JOIN tag_types AS tt ON (mfc.tag_type_id=tt.tag_type_id)
				";
		return parent::getAll_nvp('display_name', $orderBy, $constraints, $sql);
	}
}