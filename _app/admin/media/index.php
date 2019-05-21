<?php 

use crazedsanity\core\ToolBox;
use \cms\cms\core\media;
use \cms\mediaFolder;

//ToolBox::$debugPrintOpt=1;

if($_POST) {
	\debugPrint($_POST, "Post data");
	
	$alert = "No tags selected or no items selected.";
	if(is_array($_POST['tags']) && isset($_POST['in']['tags'])) {
		$selectedTags = explode(',', $_POST['in']['tags']);
		
		
		\debugPrint($selectedTags, "Selected tags");
		$results = 0;
		
		$mediaObj = new \cms\media($db);
		foreach($_POST['tags'] as $mediaId=>$junk) {
			$results += $mediaObj->addTags($mediaId, $selectedTags);
		}
		$alert = "Tags applied, ". $results ." added/created.";
	}
	
	
	$location = $_SERVER['PHP_SELF'] . '?alert='. urlencode($alert);
	ToolBox::conditional_header($location);
	exit;
}
else {
	
	
	$_TEMPLATE['PAGE_TITLE'] = 'Media';
	$_TEMPLATE['keyName'] = 'media_id';
	
	
	$mediaObj = new media($db);
	
	$allMedia = $mediaObj->getAll_structured(true);
	
	\debugPrint($allMedia, "All Media");
	
	
	
	// Use a template to build the page.
	$_mainTmpl = getTemplate('update/media/index.tmpl');
	$_mediaRow = $_mainTmpl->setBlockRow('mediaRow');
	$_folderRow = $_mainTmpl->setBlockRow('folderRow');
	
	
	
	if(!$acl->access($_SESSION['MM_Username'], 'media', 0, ADD)){
		$_mainTmpl->addVar('pageOptionsClass', 'hidden');
	}
	
	$renderedRows = "";
	
	foreach($allMedia as $id=>$record) {
		$_folderRow->reset();
		$mainRecord = $record;
		unset($mainRecord[\cms\media::CHILDFOLDER]);
		
		if(count($mainRecord) == 0) {
			// Items that have no parent will be inside a blank array.
			$mainRecord['parent_filename'] = " - SITE ROOT - ";
			$_folderRow->addVar('folderRowDeleteClass', 'hidden');
			$_folderRow->addVar('folderRowEditClass', 'hidden');
		}
		else {
			if(isset($mainRecord['parent_deleteable']) && !$mainRecord['parent_deleteable']) {
				$_folderRow->addVar('folderRowDeleteClass', 'hidden');
			}
		}
		
		$childRecords = array();
		if(isset($record[\cms\media::CHILDFOLDER])) {
			$childRecords = $record[\cms\media::CHILDFOLDER];
		}
		
		$renderedSubRows = "";
		
		// Remove the delete option if there are files associated with it.
		$_folderRow->addVar('folderRowDeleteClass', '');
		if(count($childRecords) > 0) {
			$_folderRow->addVar('folderRowDeleteClass', 'invisible');
		}
		
		// handle orphaned files (no parent folder)
		if(isset($record[\cms\media::ORPHANFOLDER])) {
			$_folderRow->addVar('folderRowDeleteClass', 'invisible');
			$childRecords = $record[\cms\media::ORPHANFOLDER];
		}
		
		foreach($childRecords as $childRecord) {
			$_mediaRow->addVar('preview', "");
			$_mediaRow->addVar('hover', "");
			if($base->is_image($childRecord['filename']) && file_exists(MEDIA_DIR . '/' . $childRecord['filename'])) {
				$_mediaRow->addVar('isImageClass', 'image');
//				debugPrint($childRecord, "appears to be an image");
			}
			else {
				$_mediaRow->addVar('isImageClass', '');
			}
			
			$_mediaRow->addVar("deleteOption", "");
			if(!$childRecord['deleteable']) {
				$_mediaRow->addVar('mediaRowDeleteClass', 'invisible');
			}
			
			$childRecord['title'] = $childRecord['display_filename'];
			
			//forge some stuff into our record so they can be parsed into the template
			$childRecord['parentIndex'] = $id;
			
			$_mediaRow->addVarList($childRecord);
			$renderedSubRows .= $_mediaRow->render();
		}
		
		$_folderRow->addVarList($mainRecord);
		$_folderRow->addVar("mediaRow", $renderedSubRows);
		$_folderRow->addVar('display_name', $record['display_name']);
		$renderedRows .= $_folderRow->render();
	}
	
	
	$_mainTmpl->addVar('folderRow', $renderedRows);
	echo $_mainTmpl->render();
}
