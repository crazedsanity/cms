<?php

ini_set('display_errors', false);
require_once(__DIR__ .'/../_app/core.php');

ini_set('display_errors', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);

use crazedsanity\core\ToolBox;
use cms\cms\core\Database;
use cms\cms\core\page;
use cms\cms\core\mediaFolder;
use cms\cms\core\media;
use cms\cms\core\menu;
use cms\cms\core\menuItem;
use cms\cms\core\gallery;
use cms\cms\core\galleryPhotos;

ToolBox::$debugPrintOpt = 1;

$useIni = false;
if(file_exists(__DIR__ . '/../config/siteconfig-dev.ini')) {
	$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig-dev.ini',true);
}
elseif(file_exists(__DIR__ . '/../config/siteconfig.ini')) {
	$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig.ini', true);
}

//debugPrint($useIni, "INI data");

if(is_array($useIni) && isset($useIni['import_database'])) {
	
}
else {
	throw new \Exception("No configuration file, or missing section");
}




class _import extends cms\cms\core\core {
	
	protected $importDb;
	protected $db;
	
	public function __construct(array $useIni) {
		$this->importDb = new Database(
				$useIni['import_database']['server'],
				$useIni['import_database']['user'],
				$useIni['import_database']['pass'],
				$useIni['import_database']['database']
		);
		$this->db = new Database(
				$useIni['database']['server'],
				$useIni['database']['user'],
				$useIni['database']['pass'],
				$useIni['database']['database']
		);
		
		ToolBox::$debugRemoveHr = 1;
		
		$this->importSettings();
		$this->importPages();
		$this->importMedia();
		$this->importMenus();
		$this->importGalleries();
		$this->importAdmins();
	}
	
	
	protected function _cleanRecord(array $validColumns, array $existingRecord) {
		if(count($validColumns) && count($existingRecord)) {
			
			$tested = 0;
			foreach($validColumns as $k=>$v) {
				if(is_numeric($k)) {
					$tested++;
				}
			}
			if($tested !== count($validColumns)) {
//				$this->debugPrint($validColumns, "looks like 'validColumns' is actually data... getting keys");
				$validColumns = array_keys($validColumns);
			}
			
			// only use valid columns from the old data.
			$insertData = array();
			foreach($existingRecord as $colName=>$colVal) {
				if(in_array($colName, $validColumns)) {
					$insertData[$colName] = $colVal;
				}
//				else {
//					$this->debugPrint($colName, "skipping column");
//				}
			}

			if(count($insertData)) {
				return $insertData;
			}
			else {
				$this->debugPrint(func_get_args(), "argument data");
				throw new exception(__METHOD__ ." - no data to insert");
			}
		}
		else {
			throw new InvalidArgumentException(__METHOD__ ." - no data in one or more arguments");
		}
	}
	
	
	public function importPages() {
		// remove all existing pages in the current database.
		$deletedPages = $this->db->run_query("DELETE FROM pages");
		$this->debugPrint($deletedPages, "pages deleted from new database");
		
		$numExistingRecords = $this->importDb->run_query("SELECT * FROM pages");
		$oldData = $this->importDb->farray_fieldnames();
		
		$oldPk = null;
		$firstRecord = $oldData[array_keys($oldData)[0]];
		
		$oldById = array();
		
		if(isset($firstRecord['page_id'])) {
			$oldPk = 'page_id';
		}
		elseif(isset($firstRecord['id'])) {
			$oldPk = 'id';
		}
		
		if(!is_null($oldPk)) {
			foreach($oldData as $k=>$v) {
				$oldById[$v[$oldPk]] = $v;
			}
		}
//		exit;
		
		// create a test page.
		$pageObj = new page($this->db);
		$testId = $pageObj->insert(array('title'=>'test'));
		
		$queryRes = $this->db->run_query("SELECT * FROM pages WHERE page_id=". $testId);
		
		$numImported = 0;
		
		if($queryRes == 1) {
			$templateRecord = $this->db->get_single_record();
			
			// get rid of the template record.
			$pageObj->delete($testId);
			
			$validColumns = array_keys($templateRecord);

			foreach($oldData as $k=>$v) {
				$createPage = true;
				$insertData = $this->_cleanRecord($validColumns, $v);
				
				// attempt to preserve ID's
				if(!is_null($oldPk)) {
					$insertData['page_id'] = $v[$oldPk];
					
					if(intval($v['parent_id']) > 0 && !isset($oldById[$v['parent_id']])) {
						$createPage = false;
						unset($insertData['parent_id']);
					}
//					if(isset($oldById))
				}
				else {
					unset($insertData['parent_id']);
				}
				
				if($createPage === true) {
					// now insert the data.
					$importId = $pageObj->insert($insertData);
					$numImported++;
				}
			}
		}
		else {
			$this->debugPrint($queryRes, "result of query");
			$this->debugPrint($testId, "No page found, ID created was");
			throw new Exception("No record found");
		}
		$this->debugPrint("{$numImported}/{$numExistingRecords}", "imported/existing page records", 1, 0);
		
		return $numImported;
	}
	
	
	public function importMedia() {
		// delete all existing media.
		$deletedRecords = $this->db->run_query("DELETE FROM media");
		$this->debugPrint($deletedRecords, "Media records removed from new database");
		
		$_mfObj = new mediaFolder($this->db);
		$criteria = array('display_name'=>'IMPORTED');
		try {
			$this->db->run_query("SELECT * FROM media_folders WHERE display_name=:display_name", $criteria);
			$data = $this->db->get_single_record();
			
			if(!empty($data)) {
				$mediaFolderId = $data[$_mfObj->pkey];
				$this->debugPrint($data, "found media folder");
			}
			else {
				// create the folder.
				$mediaFolderId = $_mfObj->insert($criteria);
				$this->debugPrint($mediaFolderId, "created media folder");
			}
		}
		catch(Exception $ex) {
			$this->debugPrint($ex->getMessage(), "no existing media folders...??? ");
			$mediaFolderId = $_mfObj->insert($criteria);
			$this->debugPrint($criteria, "created a new media folder");
		}
		$this->debugPrint($mediaFolderId, "Folder ID for new media");
		
		if(intval($mediaFolderId) == 0) {
			throw new Exception("Failed to create new media folder");
		}
		$defaultFolderId = $mediaFolderId;
		
		$mediaObj = new media($this->db);
		
		// now create a simple new record.
		$newId = $mediaObj->insert(array('display_filename'=>'test', 'media_folder_id'=>$mediaFolderId));
		$this->debugPrint($newId, "test media record created");
		$templateRecord = $mediaObj->simpleGet($newId);
		$validColumns = array_keys($templateRecord);
		
		// get rid of the template record.
		$mediaObj->delete($newId);
		
		
		
		// create media folders.
		$mapOld2New = array();
		$nameMap = array();
		if($this->importDb->run_query("SELECT media_id, filename FROM media WHERE is_folder=1 ORDER BY filename, media_id")) {
			$oldFolders = $this->importDb->farray_fieldnames();
			foreach($oldFolders as $k=>$v) {
				$useFolderName = trim($v['filename']);
				// create the folder.
				$crit = array(
					'display_name'	=> $useFolderName
				);
				
				
				$this->db->run_query("SELECT * FROM media_folders WHERE display_name=:display_name", $crit);
				$data = $this->db->get_single_record();

//				debugPrint($crit, "creating new folder");

				if(!empty($data)) {
					$newFolderId = $data[$_mfObj->pkey];
					$this->debugPrint($data, "found media folder");
				}
				else {
					// create the folder.
					$newFolderId = $_mfObj->insert($crit);
//					$this->debugPrint($mediaFolderId, "created media folder");
				}
				
				if(!isset($nameMap[$useFolderName])) {
					$nameMap[$useFolderName] = $newFolderId;
				}
				else {
					// duplicate folder name.
					
					debugPrint($nameMap, "existing folder name ({$useFolderName})");
					debugPrint($newFolderId, "should get mapped to this folder id ({$v['media_id']} -> {$newFolderId})");
//					exit(1);
				}
				$mapOld2New[$v['media_id']] = $newFolderId;
			}
		}
		
		
		$numExistingRecords = $this->importDb->run_query("SELECT * FROM media WHERE is_folder=0");
		
		$numImported = 0;
		$numSkipped = 0;
		
		
		// Use a single, massive INSERT instead of one per record.
		if($numExistingRecords) {
			$linePrefix = " ...  importing media... ";
			$existingRecords = $this->importDb->farray_fieldnames();
			$insertList = array();
			foreach($existingRecords as $k=>$v) {
				$insertData = $this->_cleanRecord($validColumns, $v);
				
				$mediaFolderId = $defaultFolderId;
				if(isset($mapOld2New[$v['parent_id']])) {
					$mediaFolderId = $mapOld2New[$v['parent_id']];
				}
				$insertData['media_folder_id'] = $mediaFolderId;
				
				// don't allow invalid records
				if(!is_null($v['filename'])) {
					$insertData['display_filename'] = $v['filename'];

//					$importRecord = $mediaObj->insert($insertData);
//					$this->debugPrint($importRecord, "imported media record");
					
					$insertList[] = $insertData;
					$numImported++;
					
				
				}
				else {
					$numSkipped++;
//					$this->debugPrint($v, "invalid record...?");
				}
				echo "\r$linePrefix   ". $numImported ." of $numExistingRecords (skipped ". $numSkipped .")  " ;
			}
			echo "\n";
			

			// now do the massive insert.
			$sql = "INSERT INTO media (". join(',', array_keys($insertData)) .") VALUES ";
			$params = array();
			foreach($insertList as $i=>$insertData) {
				$thisRecord = '';
				foreach($insertData as $field=>$value) {
					$paramName = $field . $i; // e.g. filename0
					$params[$paramName] = $value;
					$thisRecord = ToolBox::create_list($thisRecord, ":{$paramName}");
				}

				$sql .= "\n\t";
				if($i > 0) {
					$sql .= ",";
				}
				$sql .= "({$thisRecord})";
			}
//			debugPrint($sql, "SQL for mass insert");
//			debugPrint($params, "parameter list for mass insert");
			debugPrint($numImported, "Going to mass-insert media recors");
			$insertRes = $this->db->run_insert($sql, $params);
			debugPrint($insertRes, "result of mass insert (this will be an ID)");
			
			
		}
		else {
			$this->debugPrint($numExistingRecords, "no existing media...?");
		}
		$this->debugPrint("{$numImported}/{$numExistingRecords}", "imported/existing media records", 1, 0);
	}
	
	
	public function importMenus() {
		// delete the existing information.
		$deletedItems = $this->db->run_query("DELETE FROM menu_items");
		$this->debugPrint($deletedItems, "Deleted existing menu items");
		$deletedMenus = $this->db->run_query("DELETE FROM menus");
		$this->debugPrint($deletedMenus, "Deleted existing menus");
		
		// create a template record.
		$_mObj = new menu($this->db);
		$testMenuId = $_mObj->insert(array('name'=>'test'));
		$templateMenu = $_mObj->simpleGet($testMenuId);
		
		// create a template item.
		$_miObj = new menuItem($this->db);
		$testItemId = $_miObj->insert(array('title' => 'test', 'menu_id'=>$testMenuId));
		$templateItem = $_miObj->simpleGet($testItemId);
		
		// remove the unecessary test records
		$_miObj->delete($testItemId);
		$_mObj->delete($testMenuId);
		
		
		$eMenuObj = new menu($this->importDb);
		$eItemObj = new menuItem($this->importDb);
		
		// menus
		$existingMenus = $eMenuObj->simpleGetAll();
		$numExistingMenus = count($existingMenus);
		$menusImported = 0;
		foreach($existingMenus as $k=>$v) {
			$insertData = $this->_cleanRecord($templateMenu, $v);
			$_mObj->insert($insertData);
			$menusImported++;
		}
		$this->debugPrint("{$menusImported}/{$numExistingMenus}", "imported/existing menus");
		
		
		$existingItems = $eItemObj->simpleGetAll();
		$numExistingItems = count($existingItems);
		$itemsImported = 0;
		
		foreach($existingItems as $k=>$v) {
			try {
				$_miObj->insert($this->_cleanRecord($templateItem, $v));
				$itemsImported++;
			}
			catch(Exception $ex) {
				$this->debugPrint($ex->getMessage(), "failed to insert record");
				$this->debugPrint($v, "record data that failed");
				
				$this->debugPrint($_mObj->simpleGet($v['menu_id']), "associated menu");
				
				throw $ex;
			}
		}
		$this->debugPrint("{$itemsImported}/{$numExistingItems}", "imported/existing menu items");
				
	}
	
	
	public function importGalleries() {
		$oGal = new gallery($this->importDb);
		$oGalP = new galleryPhotos($this->importDb);
		$nGal = new gallery($this->db);
		$nGalP = new galleryPhotos($this->db);
		$deleted = $this->db->run_query("DELETE FROM galleries");
		$this->debugPrint($deleted, "galleries deleted from new database");
		$deletedPhotos = $this->db->run_query("DELETE FROM gallery_photos");		
		$this->debugPrint($deletedPhotos, "gallery photos deleted from new database");
		
		try {
			$oGal->pkey = null;
			$existingGals = $oGal->simpleGetAll();
		}
		catch(Exception $ex) {
			$this->debugPrint($this->importDb, "Db");
			$this->debugPrint($ex->getMessage(), "STACK TRACE");
			exit(__FILE__ ." - line #". __LINE__ ."\n");
		}
		$numExistingGals = count($existingGals);
		if($numExistingGals > 0) {
			$createdGals=0;
			foreach($existingGals as $k=>$v) {
				if(isset($v['photo_id'])) {
					unset($v['photo_id']);
				}
				$nGal->insert($v);
				$createdGals++;
			}
			$this->debugPrint("{$createdGals}/{$numExistingGals}", "imported/existing galleries");
			
			
			$createdPhotos = 0;
			$existingPhotos = $oGalP->simpleGetAll();
			$numExistingItems = count($existingPhotos);
			foreach($existingPhotos as $k=>$v) {
				$nGalP->galleryId = $v['gallery_id'];
				if(isset($v['photo_id'])) {
					unset($v['photo_id']);
				}
				if(isset($v['promo_position'])) {
					unset($v['promo_position']);
				}
				if(empty($v['description'])) {
					$v['description'] = '';
				}
				$nGalP->insert($v);
				$createdPhotos++;
			}
			$this->debugPrint("{$createdPhotos}/{$numExistingItems}", "imported/existing gallery items");
		
		}
		else {
			$this->debugPrint($existingGals, "no existing galleries");
		}
	}
	
	
	public function importSettings() {
		if($this->db->run_query("SELECT * FROM settings LIMIT 1")) {
			$data = $this->db->get_single_record();
			unset($data['setting_id'], $data['asset_id']);
			$validColumns = array_keys($data);
		}
		else {
			throw new \LogicException("no existing settings, unable to incorporate");
		}
		
		
		$numSettings = $this->importDb->run_query("SELECT * FROM settings");
		if($numSettings > 0) {
			$oldSettings = $this->importDb->farray_fieldnames();
			
			foreach($oldSettings as $k=>$v) {
				$cleaned = $this->_cleanRecord($validColumns, $v);
				
				if($this->db->run_query("SELECT * FROM settings WHERE title=:name", array('name'=>$cleaned['title']))) {
					$theRecord = $this->db->get_single_record();
					
					// do an update.
					$sql = "UPDATE settings SET ";
					$i=0;
					$params = $cleaned;
					foreach($cleaned as $k=>$v) {
						if($i > 0) {
							$sql .= ", ";
						}
						$sql .= "{$k}=:{$k}";
						$i++;
					}
					$sql .= " WHERE setting_id=:id";
					$params['id'] = $theRecord['setting_id'];
					$this->db->run_update($sql, $params);
				}
				else {
					debugPrint($cleaned['title'], "no record found for setting");
					try {
						$this->db->insert('settings', $cleaned);
					}
					catch(Exception $ex) {
						debugPrint($ex->getMessage(), "error while doing insert");
						throw $ex;
					}
				}
			}
			
//			debugPrint($importThis, "data for import");
		}
		else {
			debugPrint($numSettings, "No settings found...?");
		}
	}
	
	
	public function importAdmins() {
		
		// attempt to pull all records for the groups, so they get created properly.
		$numImportGroups = $this->importDb->run_query("SELECT * FROM groups");
		$this->debugPrint($numImportGroups, "number of groups to import");
		if($numImportGroups > 0) {
			$importGroups = $this->importDb->farray_fieldnames();
			$bulkGrouper = new cms\database\bulkInsert('groups', array_keys($importGroups[0]));
			
			
			foreach($importGroups as $k=>$v) {
				$bulkGrouper->add($v);
			}
			
			// now wipe out existing groups...
			$truncateRes = $this->db->run_query("TRUNCATE TABLE groups");
			$this->debugPrint($truncateRes, "result of truncating groups");
			$numRecordsAfterTruncate = $this->db->run_query("SELECT * FROM groups");
			$this->debugPrint($numRecordsAfterTruncate, "number of records after TRUNCATE (anything greater than 0 is bad)");
			if($numRecordsAfterTruncate !== 0) {
				throw new \LogicException("TRUNCATE TABLE statement for groups table failed, MySQL is borked");
			}
			
			// looks like we're good to do a mass insert... (should we do this later?)
			$createRes = $bulkGrouper->doInsert($this->db);
			$this->debugPrint($createRes, "result of mass insert");
		}
		$this->debugPrint(__LINE__, "finished handling groups, this is line #");
		
		// now handle admin accounts.
		$numAdmins = $this->importDb->run_query("SELECT * FROM admins");
		if($numAdmins > 0) {
			$importAdmins = $this->importDb->farray_fieldnames();
			$useForFieldList = $importAdmins[0];
			$useForFieldList['user_id'] = $useForFieldList['admin_id'];
			unset($useForFieldList['admin_id']);
			
			$bulkAdminner = new cms\database\bulkInsert('users', array_keys($useForFieldList));
			
			$progressLine = "\r --- Records hashed: %s of %s (%s%%)";
			$percentComplete = 0;
			echo "\n". sprintf($progressLine, 0, $numAdmins, "0.0");
			echo "\n";
			foreach($importAdmins as $k=>$v) {
				// gotta hash the password.
				$v['password'] = password_hash($v['password'], PASSWORD_DEFAULT);
				
				// now fix primary key name/value.
				$v['user_id'] = $v['admin_id'];
				unset($v['admin_id']);
				
				// fix invalid dates.
				if(preg_match('/00\-00/', $v['created']) === 1) {
					$v['created'] = null;
				}
				
				
				$i = $bulkAdminner->add($v);
				
				$percentComplete = number_format((($i / $numAdmins)*100), 1);
				
				$renderedProgress = sprintf($progressLine, $i, $numAdmins, $percentComplete);
				if($renderedProgress === false) {
					debugPrint($i, "value of i");
					debugPrint($percentComplete, "value of percentComplete");
					throw new \LogicException("string failed to parse");
				}
				echo $renderedProgress;
			}
			echo "\n";
			
			// wipe out existing users in the new CMS
			$this->db->run_query("TRUNCATE TABLE users");
			
			// all is well, do the mass insert.
			$userCreateRes = $bulkAdminner->doInsert($this->db);
			$this->debugPrint($userCreateRes, "result of creating users");
			
			// do a final update.
			$setActiveRes = $this->db->run_update("UPDATE users SET is_active=1 WHERE user_id >= 0");
			$this->debugPrint($setActiveRes, "result of setting all users as active");
		}
		
		// update description for the admin group.
		$this->db->update(
				'groups', 
				array(
					'description'	=> "Administrative group, usually with full access."
				), 
				"name='admin'");
		
		// now that we've handled creating users and groups, link 'em together.
		$numImportUG = $this->importDb->run_query("SELECT * FROM admin_groups");
		$this->debugPrint($numImportUG, "number of admin groups to import (as user_groups)");
		if($numImportUG > 0) {
			$fieldMap = array('user_group_id', 'user_id', 'group_id');
			$bulkUG = new cms\database\bulkInsert('user_groups', $fieldMap);
			
			$importUG = $this->importDb->farray_fieldnames();
			
			$this->debugPrint($numImportUG, "mapping old records/fields to new records/fields");
			$numProcessed = 0;
			foreach($importUG as $k=>$v) {
				$importRecord = array(
					'user_group_id'	=> $v['admin_group_id'],
					'user_id'		=> $v['admin_id'],
					'group_id'		=> $v['group_id'],
				);
				$numProcessed = $bulkUG->add($importRecord);
			}
			$this->debugPrint($numProcessed, "We've processed this many records (should match total from above)");
			
			// do the mass insert.
			$UGCreateRes = $bulkUG->doInsert($this->db);
			$this->debugPrint($UGCreateRes, "result of creating user_group mappings");
		}
		
		/* 
		 * CMS ACCESS
		 * 
		 * Give the existing users CMS access.  Previously (in versions of KKCMS 
		 * that weren't stored on BitBucket), CMS access was allowed simply by 
		 * giving a valid username+password combination.  Later versions required 
		 * the user to be part of the "admin" group... this was a fail because 
		 * that group usually had permission for everything.  So a new group, "CMS", 
		 * was created specifically for allowing users access.  This way a person 
		 * can log in but not access the CMS admin section.
		 */
		$cmsGroupId = $this->db->insert('groups', array('name' => "CMS", 'description' => "Users in this group will have access to the CMS admin section (/update/)"));
		$this->debugPrint($cmsGroupId, "group_id for CMS access");
		$cmsAccessGranted = 0;
		foreach($importAdmins as $k=>$v) {
			$insertData = array(
				'group_id'	=> $cmsGroupId,
				'user_id'	=> $v['admin_id']
			);
			$this->db->insert('user_groups', $insertData);
			$cmsAccessGranted++;
		}
		$this->debugPrint($cmsAccessGranted, "number of users that were granted access to the CMS admin");
		
		
		
		
		/* 
		 * So.. is the ACL table FUBAR?  Maybe should check this (in case the group_id of "Admin" isn't 0).
		 * 
		 * For now, let's just deal with ACL's that need to be translated. GUH.
		 */
		$numImportAcl = $this->importDb->run_query("SELECT * FROM acl"); // don't handle admin group records...
		$this->debugPrint($numImportAcl, "number of ACL records to import");
		if($numImportAcl > 0) {
			// Should we truncate the table first...?
			
			
			// retrieve records.
			$importAcl = $this->importDb->farray_fieldnames();
			
			$countsByGid = array();
			
			$fieldList = $importAcl[0];
			$fieldList['user_id'] = $fieldList['admin_id'];
			unset($fieldList['admin_id'], $fieldList['acl_id']);
			
			// setup the bulk inserter.
			$bulkAcl = new cms\database\bulkInsert('acl', array_keys($fieldList));
			
			// now start pumping records into the bulk inserter.
			$numProcessed = 0;
			foreach($importAcl as $k=>$v) {
				//fix the data.
				$v['user_id'] = $v['admin_id'];
				if(!isset($countsByGid[$v['group_id']])) {
					$countsByGid[$v['group_id']] = 0;
				}
				$countsByGid[$v['group_id']]++;
				unset($v['admin_id'], $v['acl_id']);
				
				
				try {
					$numProcessed =  $bulkAcl->add($v);
					
				} catch (Exception $ex) {
					$this->debugPrint($v, "failed while adding this record");
					throw $ex;
				}
			}
			
			// now do the insert.
			$aclImportRes = $bulkAcl->doInsert($this->db);
			$this->debugPrint($aclImportRes, "result of creating user-group records");
		}
	}
}



try {
$ImportObj = new _import($useIni);
} 
catch(Exeception $ex) {
	exit(1);
}
