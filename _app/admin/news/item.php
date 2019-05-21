<?php

use crazedsanity\core\ToolBox;
//ini_set('display_errors', true);
//ToolBox::$debugPrintOpt = 1;

$adminId = $_SESSION['MM_UserID'];
$adminUser = $_SESSION['MM_Username'];

if(isset($clean['news_id'])) {
	$news_id = intval($clean['news_id']);
} else {
	$news_id = 0;
}

$access = false;

if($news_id > 0 && ( $acl->access($_SESSION['MM_Username'], 'news', $news_id, EDIT) )) {
	$access = true;
} else if($news_id <= 0 && ( $acl->access($_SESSION['MM_Username'], 'news', $news_id, ADD) )) {
	$access = true;
}

//if($access) {
	$db->debug=0;
	debugPrint($_FILES, "FILEs array");
	debugPrint($_POST, "POSTed data");
	if(isset($_POST["news"]["title"])) {
		
		$newsObj = new cms\cms\core\news($db);
		$mediaObj = new \cms\cms\core\media($db);
		
		if(isset($_POST['removeimage'])) {
			
			$currentInfo = array();
			if(intval($_POST['news_id']) > 0) {
				$currentInfo = $newsObj->get($_POST['news_id']);
				debugPrint($currentInfo, "Current news info");
				if(intval($currentInfo['media_id']) > 0) {
					$existingMediaInfo = $mediaObj->get($currentInfo['media_id']);
					debugPrint($existingMediaInfo, "existing media info");
					
					$clean['news']['media_id'] = null;
					if(is_array($existingMediaInfo) && count($existingMediaInfo) > 0 && intval($existingMediaInfo['media_id']) > 0) {
						$delRes = $mediaObj->delete($existingMediaInfo['media_id']);
						addAlert("Media Deleted", "The media item was deleted (". $delRes .")", "status");
					}
					else {
						addAlert("Missing Media", "The original media item attached to this record was missing", "status");
					}
				}
			}
		}
		elseif(!empty($_FILES) && $_FILES['image']['size'] > 0) {
			$mfObj = new \cms\cms\core\mediaFolder($db);
			$allFolders = $mfObj->getAll();
			if(count($allFolders) > 0) {
				$folder_id = array_keys($allFolders)[0];
			}
			
			$insertData = array(
				'media_folder_id'	=> $folder_id,
				'admin_id'			=> $adminId,
				'user'				=> $adminUser,
			);

			$currentInfo = array();
			$mediaUploadType = 'insert';
			if(intval($_POST['news_id']) > 0) {
				$currentInfo = $newsObj->get($_POST['news_id']);
				debugPrint($currentInfo, "Current news info");
				if(intval($currentInfo['media_id']) > 0) {
					$existingMediaInfo = $mediaObj->get($currentInfo['media_id']);
					debugPrint($existingMediaInfo, "existing media info");
					if(is_array($existingMediaInfo) && count($existingMediaInfo) > 0) {
						$mediaUploadType = 'update';
						$insertData['media_id'] = $existingMediaInfo['media_id'];
					}
					else {
						addAlert("Missing Media", "The original media item attached to this record was missing", "status");
					}
				}
			}
			
			
			$mediaTitle = "News Item (new)";
			if($news_id > 0) {
				$mediaTitle = "News Item #". $news_id;
			}
			$insertData['display_filename'] = $mediaTitle;
			
			$mediainfo = $mediaObj->upload('image', '/data/upfiles/media', $insertData, $mediaUploadType);
			debugPrint($mediainfo, "uploaded media info");
			addAlert("Media Uploaded", "New media was uploaded (". $mediainfo .")", "status");
			$clean['news']['media_id'] = $mediainfo;
		}
		else {
			debugPrint($_FILES, "no files uploaded");
		}
		
		if(!isset($clean['news']['media_id']) || !is_numeric($clean['news']['media_id'])) {
			$clean['news']['media_id'] = 0;
		}


//		exit(__FILE__ ." - line #". __LINE__);

		$clean['news']['modified'] = date('Y-m-d H:i:s', time());
		
		$clean['news']['start_date'] = ( $clean['news']['start_date'] ) ? $clean['news']['start_date'] : date('Y-m-d');
		$clean['news']['end_date'] = ( $clean['news']['end_date'] ) ? $clean['news']['end_date'] : '2099-12-31';
		
		// 
		if(isset($clean['news']['start_time']) && !empty($clean['news']['start_time'])) {
			$clean['news']['start_date'] .= ' '. date_format(date_create($clean['news']['start_time']), 'H:i');
		}
		unset($clean['news']['start_time']);
		if(isset($clean['news']['end_time']) && !empty($clean['news']['end_time'])) {
			$clean['news']['end_date'] .= ' '. date_format(date_create($clean['news']['end_time']), 'H:i');
		}
		unset($clean['news']['end_time']);
		debugPrint($clean['news'], "POSTed news info");

		if(!empty($clean['news']['start_date'])) {
			$clean['news']['start_date'] = date('Y-m-d H:i:s', strtotime($clean['news']['start_date']));
		}
		if(!empty($clean['news']['end_date'])) {
			$clean['news']['end_date'] = date('Y-m-d H:i:s', strtotime($clean['news']['end_date']));
		}

		if(!empty($_POST['news_id'])) {
			// Update
			$news_id = intval($clean["news_id"]);
			$updateRes = $db->update("news", $clean["news"], "news_id=" . $news_id);
			addAlert("Article Updated", "The news article was updated (". $updateRes .")", "notice");
		} else { // Insert
			// Unset the hidden id
			unset($_POST["news_id"]);
			$news_id = $db->insert("news", $clean["news"]);
			addAlert("Article Created", "News article #". $news_id ." was created successfully", "notice");
			
			// update the title of the media item.
			if($clean['news']['media_id'] > 0 && is_array($insertData) && isset($insertData['display_filename'])) {
				$updateRes = $mediaObj->update(array('display_filename'=>$insertData['display_filename']), $clean['news']['media_id']);
				addAlert("Media Title Updated", "The attached media item title was updated (". $updateRes .")", "status");
			}
		}
		$location = $pageUrl ."?news_id=" . $news_id;
		

		if(!debugPrint("<a href='{$location}'>{$location}</a>", "Woulda redirected, but you're debugging.  Here's the link")) {
			ToolBox::conditional_header($location);
		}
		exit;
	} else {

		if(isset($clean['news_id'])) {
			$news_id = intval($clean['news_id']);
		} else {
			$news_id = 0;
		}

		if($news_id > 0) {
			$sql = <<<SQL
					SELECT
						n.news_id, n.title, n.byline, n.description, n.approved, 
						n.start_date, n.end_date, m.filename, m.media_id, n.front_page
					FROM news n
					LEFT JOIN media m ON m.media_id = n.media_id
					WHERE n.news_id='{$news_id}'
SQL;
			$rs = $db->query_first($sql);
		}
		else {
			$rs = array();
		}
	}
//}
//else {
//	$goHere = dirname($_SERVER['REQUEST_URI']) .'?alert='. urlencode(strip_tags(acl::accessDeniedMsg()));
//	ToolBox::conditional_header($goHere);
//	exit;
//}



$_tmpl = getTemplate('update/news/item.html');
$_tmpl->addVarList($rs);


debugPrint($rs, "data");

$isApproved = 1;
$isFrontPage = 0;
$existingFilename = '';
if(is_array($rs) && count($rs) > 0) {
	$isApproved = $rs['approved'];
	$isFrontPage = $rs['front_page'];
	$existingFilename = $rs['filename'];
	if(isset($rs['start_date'])) {
		$startDateBits = explode(' ', $rs['start_date']);
		if(count($startDateBits) == 2) {
			$_tmpl->addVar('start_date', $startDateBits[0]);
			$_tmpl->addVar('start_time', $startDateBits[1]);
		} 
	}
	if(isset($rs['end_date'])) {
		$endDateBits = explode(' ', $rs['end_date']);
		if(count($endDateBits) == 2) {
			$_tmpl->addVar('end_date', $endDateBits[0]);
			$_tmpl->addVar('end_time', $endDateBits[1]);
		}
	}
}

$_tmpl->addVar('approvedOptionList', $base->makeOptionsList(array('1' => 'Yes', '0' => 'No'), $isApproved));
$_tmpl->addVar('frontPageOptionList', $base->makeOptionsList(array('1' => 'Yes', '0' => 'No'), $isFrontPage));
$_tmpl->addVar('adminMediaInput', $base->adminMediaInput($existingFilename, 'Image - 430x300 (pixels)', 'image'));



echo $_tmpl->render(true);
