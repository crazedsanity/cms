<?php

use \cms\cms\core\media;
use \cms\mediaFolder;
use \cms\mediaFolderConstraint;
use \cms\tagType;
use \cms\tag;
use cms\mediaTag;

//error_reporting(E_ALL);
//ini_set('display_errors', true);

use crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt = 1;

$_obj = new media($db);
$_fObj = new mediaFolder($db);
$_tt = new tagType($db);
$_tags = new tag($db);
$_mTags = new mediaTag($db);


$id = null;
$type = null;
$action = null;

$canEdit = false;
$canAdd = false;


if(isset($clean['media_id'])) {
	$media_id = intval($clean['media_id']);
} else {
	$media_id = 0;
}

$adminId = $_SESSION['MM_UserID'];
$adminUser = $_SESSION['MM_Username'];

//TODO: fix this code, it's G-ROSS.
$access = false;
if($media_id > 0 && ( $acl->access($_SESSION['MM_Username'], 'media', $media_id, EDIT) )) {
	$access = true;
}
else if($media_id <= 0 && ($acl->access($_SESSION['MM_Username'], 'media', $media_id, ADD))) {
	$access = true;
}

		

if(!empty($_POST)) {// trying to change something.
	debugPrint($_POST, "POSTed data");
	debugPrint($_FILES, "uploaded files data");
	
	if(!empty($_POST['type'])) {
		$type = $_POST['type'];
		
		if(!empty($_POST['action'])) {
			$action = $_POST['action'];
			
			if($type === 'media') {
				// figure out the target path.
				if(isset($_POST['media_folder_id']) && intval($_POST['media_folder_id']) > 0) {
					try {
						$folderInfo = $_fObj->get($_POST['media_folder_id']);
						debugPrint($folderInfo, "Folder information");
						
						
						
						if(!empty($_FILES) && !empty($_FILES['image']['tmp_name'])) {
							// upload a new/replacement file, or just edit an existing one.
							$imgData = array(
								'media_folder_id'	=> $_POST['media_folder_id'],
								'admin_id'			=> $adminId,
								'user'				=> $adminUser,
							);
							
							$uploadType = 'insert';
							if($action == 'edit') {
								$uploadType = 'update';
								$imgData['media_id'] = $_POST['media_id'];
							}
							try {
								if(isset($_POST['filename']) && !empty($_POST['filename'])) {
									$imgData['display_filename'] = $_POST['filename'];
								}

								$media_id = $_obj->upload('image', $folderInfo['path'], $imgData, $uploadType);
								addAlert("Media Uploaded", "Media id #". $media_id ." was uploaded", "notice");
							} catch (Exception $ex) {
								addAlert("Upload Error", "Error uploading: ". $ex->getMessage(), "error");
							}
						}
						else {
							// just updating details.
							$changeThis = array(
								'media_folder_id'	=> $_POST['media_folder_id'],
							);
							if(!empty($_POST['filename'])) {
								$changeThis['display_filename'] = $_POST['filename'];
							}
							try {
								$res = $_obj->update($changeThis, $media_id);
								addAlert("Media Updated", "The record was successfully updated (". $res .")", "notice");
							}
							catch(Exception $ex) {
								addAlert("An Error Occurred", "The following error occurred: ". $ex->getMessage(), "error");
							}
						}
					}
					catch(Exception $ex) {
						addAlert("Database Error", "Problem encountered while retrieving folder info: ". $ex->getMessage() .'<br><b>TRACE: </b>'. nl2br($ex->getTraceAsString()), "error");
					}
				}
			}
			elseif($type === 'folder') {
				if(isset($_POST['filename']) && !empty($_POST['filename'])) {
					// add/edit a folder.
					$cleanName = $clean['filename'];
					if($action == 'add') {
						try {
							$res = $_fObj->insert(array('display_name' => $cleanName));
							addAlert("Folder Created", "The folder '". $cleanName. "' was created successfully (". $res .")", "notice");
						}
						catch(Exception $ex) {
							addAlert("Database Error", "Unable to create folder: ". $ex->getMessage(), "error");
						}
					}
					elseif($action == 'edit' && isset($_POST['media_folder_id']) && intval($_POST['media_folder_id']) > 0) {
						$mfId = intval($_POST['media_folder_id']);
						try {
							$res = $_fObj->update(array('display_name'=>$_POST['filename']), $mfId);
							addAlert("Folder Updated", "The folder was updated successfully (". $res .")", "notice");
						}
						catch(Exception $ex) {
							addAlert("Update Failed", "The update failed: ". $ex->getMessage(), "error");
						}
						
					}
					else {
						addAlert("Invalid Action", "The requested folder action was invalid or did not contain enough information.", "error");
					}
				}
				else {
					addAlert("Invalid Folder Name", "The specified name was empty or invalid.", "error");
				}
			}
			else {
				addAlert("Invalid Type", "The requested type is invalid.", "error");
			}
		}
		else {
			addAlert("Missing Information", "Your request did not contain enough information to do anything.", "error");
		}
	}
	else {
		addAlert("Missing Type", "No type was found, unable to complete the request", "error");
	}
	
	debugPrint($_SESSION, "session info");
	if(!debugPrint('<a href="/update/media">Proceed...</a>', "You're debugging, click the link to continue")) {
		ToolBox::conditional_header('/update/media/');
	}
	exit;
}
else {  // viewing something.
	
	$pageTitle = '';
	if(isset($_GET['type']) && ($_GET['type'] == 'folder' || $_GET['type'] == 'media')) {
		
		debugPrint($_GET, "GET vars");
		
		$_tmpl = getTemplate('update/media/item.tmpl');
		
		$_tmpl->addVar('type', $clean['type']);
		$_tmpl->addVar('action', $clean['action']);
		
//		$itemConstraintRow = $_tmpl->setBlockRow('itemConstraint');
//		$tagitBlock = $_tmpl->setBlockRow('tagitBlock');
//		$constraintRow = $_tmpl->setBlockRow('constraint');
		$folderRow = $_tmpl->setBlockRow('folder');
		$fileRow = $_tmpl->setBlockRow('file');
		

		$allAvailableTags = $_tags->getAll();
		debugPrint($allAvailableTags, "every tag available");
		
		
		
		
		
		if($_GET['type'] == 'folder') {
			// show the "folder" section/row
			$_tmpl->add($folderRow);
			
			
			$mfId = null;
				
			
				
			if(isset($_GET['media_folder_id']) && intval($_GET['media_folder_id']) > 0) {
				$mfId = intval($_GET['media_folder_id']);
			}
			if($_GET['action'] == 'add') {
				
			}
			elseif($_GET['action'] == 'edit' && !is_null($mfId)) {
				

				$data = $_fObj->get($mfId);
				debugPrint($data, "Folder data");
				$_tmpl->addVarList($data);
				$_tmpl->addVar('filename', $data['display_name']);
			}
			else {
				addAlert("Invalid Action", "The requested action was invalid or did not contain enough information", "error");
				ToolBox::conditional_header('/update/media/');
				exit;
			}
			
			$_tmpl->addVar('media_folder_id', $mfId);
		}
		elseif($_GET['type'] == 'media') {
			$pageTitle = 'New Media';
			// Show the "file" section/row
			
			// Show folder selection box
			$folderId = null;
			if(!empty($_GET['media_folder_id'])) {
				$folderId = intval($_GET['media_folder_id']);
			}
			



			$rs = array();
			if($_GET['action'] == 'add') {
				// Don't display the image (there isn't one).
				$fileRow->setBlockRow('image');
				debugPrint($_GET['action'], "action");
			}
			elseif($_GET['action'] == 'edit') {
				if(intval($_GET['media_id']) > 0) {
					$rs = $_obj->get(intval($_GET['media_id']));
					if(is_array($rs) && count($rs) > 0) {
						debugPrint($rs, "Media record");
						$folderId = $rs['media_folder_id'];
						if($base->is_image($rs['filename']) || $rs['filetype'] == 'application/pdf') {
							debugPrint($rs, "we can show a preview");
						}
						else {
							debugPrint($rs, "no preview");
//							$_tmpl->setBlockRow('image');
							$fileRow->setBlockRow('image');
						}
					}
					else {
						// invalid record.
						addAlert("Invalid ID", "The media ID invalid. You may have clicked an old link.", "error");
						ToolBox::conditional_header('/update/media/');
						exit;
					}
					$_tmpl->addVarList($rs);
					$_tmpl->addVar('basename', basename($rs['filename']));
					$pageTitle = $rs['display_filename'] .' ('. $rs['filename'] .')';
					debugPrint($rs, "record");
					
				}
				else {
					addAlert("Missing ID", "The media ID was missing or invalid.", "error");
					ToolBox::conditional_header('/update/media/');
					exit;
				}
			}
			else {
				addAlert("Invalid Action", "An invalid action was found (". $clean['action'] ."), maybe you have an old link...?", "error");
				ToolBox::conditional_header('/update/media/');
				exit;
			}
			

			
			
			
			$_tmpl->addVar('pageTitle', $pageTitle);
			
			$_tmpl->add($fileRow);
			
			
			
			$_tmpl->addVar('folderOptionList', $_obj->getFolderOptionList($folderId));
		}
		else {
			addAlert("System Error", "An unidentified error occurred.", "error");
			if(!debugPrint($_GET, "GET data")) {
				ToolBox::conditional_header('/update/media/');
			}
			exit;
		}
		
		
		
		
		
		
		$_tmpl->addVar('pageTitle', $pageTitle);
		echo $_tmpl->render(true);
	}
	else {
		addAlert('Invalid Type', "No valid type selected.  Please try again.  You may have clicked an out-dated link.");
		ToolBox::conditional_header('/update/media');
		exit;
	}
}

