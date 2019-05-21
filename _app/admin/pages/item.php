<?php
//$user->restrict();

use cms\cms\core\page;
use cms\cms\core\group;
use cms\cms\core\media;
use crazedsanity\core\ToolBox;

//ToolBox::$debugPrintOpt = 1;

if(debugPrint("error reporting enabled")) {
	ini_set('display_errors', true);
}


if(isset($clean['page_id'])) {
	$page_id = intval($clean['page_id']);
} else {
	$page_id = 0;
}

$access = false;
if($page_id > 0 && ( $acl->access($_SESSION['MM_Username'], 'pages', $page_id, EDIT) )) {
	$access = true;
} else if($page_id <= 0 && ( $acl->access($_SESSION['MM_Username'], 'pages', $page_id, ADD) )) {
	$access = true;
}

if($access) {
	$pageObj = new page($db);
	$mediaObj = new media($db);
	
	if(isset($clean["page"])) {
		debugPrint($_POST, "POSTed data");
		
		debugPrint($_FILES, "FILES");
		
		// set a default location...
		$location = "/update/pages/";
		
		try {
			// Save Page
			
			
			// decode body elements
			foreach(array('body', 'body_extra1', 'body_extra2', 'body_extra3') as $x) {
				if(isset($clean['page'][$x]) && !empty($clean['page'][$x])) {
					$decoded = base64_decode(strip_tags($clean['page'][$x]));
					$clean['page'][$x] = $decoded;
				}
			}

			$folder_id = 0;
			if(!empty($_POST['page_id'])) {

				$sql = "SELECT media_id, og_image_media_id FROM pages WHERE page_id = '{$mysql['page_id']}'";
				$currentinfo = $db->query_first($sql);
				$media_id = $currentinfo['media_id'];
				$media_id2 = $currentinfo['og_image_media_id'];
				$folder_id = $settings->get('gallery_media_folder', 'galleries', 0);
			}

			$mediainfo = $base->insertMediaAsset('image', $folder_id, $media_id);
			$clean['page']['media_id'] = $mediainfo->media_id;


			$mediainfo2 = $base->insertMediaAsset('ogimage', $folder_id, $media_id2);
			$clean['page']['og_image_media_id'] = $mediainfo2->media_id;

			if(empty($clean['page']['required_group_id'])) {
				$clean['page']['required_group_id'] = null;
			}

			if(!empty($clean['page_id'])) {
				// Update
				
				$page_id = intval($clean["page_id"]);
				$pageObj->update($clean["page"], $page_id);
				addAlert('Success', "Record has been updated");
				$location = "/update/pages/item.php?page_id=" . $page_id;

				// allow hero image to be uploaded...
				if(!empty($_FILES) && isset($_FILES['heroimage']) && $_FILES['heroimage']['error'] == 0) {
					try {
						$oldPageInfo = $pageObj->get($page_id);
						$uploadType = 'insert';
						$imgData = array(
							'admin_id'			=> $_SESSION['MM_UserID'],
							'user'				=> $_SESSION['MM_Username'],
							'display_filename'	=> "Homepage Hero Image",
						);
						if(intval($oldPageInfo['media_id']) > 0) {
							$imgData['media_id'] = $oldPageInfo['media_id'];
							$uploadType = 'update';
						}
						debugPrint($imgData, "data for uploaded image");
						$mediaId = $mediaObj->upload('heroimage', null, $imgData, $uploadType);
						debugPrint($mediaId, "uploaded media id");
		//				addAlert("Hero image uploaded", "Hero image has been uploaded(". $mediaId .")", "notice");

						if(intval($mediaId) > 0) {
							$heroSetRes = $pageObj->update(array('media_id' => $mediaId), $page_id);
							addAlert('Hero Image Set', "Hero image for the homepage has been updated (". $heroSetRes .")", "status");
						}
						else {
							addAlert('Hero image not set', 'Unable to update/upload the hero image ('. $mediaId .')', 'error');
						}
					}
					catch(Exception $ex) {
						debugPrint($ex->getMessage(), "Exception while handling hero image");
						addAlert("Error handling hero image", "An error occurred handling the hero image::: ". $ex->getMessage(), "error");
					}
				}
			} else {
				// Insert
				unset($clean["page_id"]);
				try {
					$page_id = $pageObj->create($clean["page"]);
					addAlert('Success', "Record has been added");
				}
				catch(Exception $e) {
					addAlert('Failed to Create Page', $e->getMessage(), 'error');
				}
				debugPrint($pageObj->get($page_id), "New page record");
				$location = "/update/pages/index.php?page_id=" . $page_id;
			}
		} 
		catch(Exception $ex) {
			addAlert("Problem Encountered", "Please report this error: ". $ex->getMessage(), "fatal");
		}

		if(!debugPrint("<a href='{$location}'>{$location}</a>", "You're debugging, so here's the link")) {
			$base->setSiteMapXML();
			crazedsanity\core\ToolBox::conditional_header($location);
		}
		exit;
	} else {

		if(isset($clean['page_id'])) {
			$page_id = intval($clean['page_id']);
		}
		else {
			$page_id = 0;
		}
		
		if(isset($clean['page_id'])) {
			$rs = $pageObj->get($page_id, false);
			
			
			if($rs['para1'] != '') {
				$rs['para1'] = '<img src="/update/_elements/img/parallax/'.$rs['para1'].'">';
					
			} else {
				$rs['para1'] = 'None';
					
			}
			
			
			
			
			if($rs['para2'] != '') {
				$rs['para2'] = '<img src="/update/_elements/img/parallax/'.$rs['para2'].'">';
			
			} else { 
				$rs['para2'] = 'None';
			
			}
		
			
			
		} else {
			$rs = array(
				'page_id' => 0,
				'parent_id' => 0,
				'title' => '',
				'keywords' => '',
				'url' => '',
				'body' => '',
				'asset' => '',
				'description' => '',
				'status' => '1',
				'parent_id'	=> '0',
				'og_image_filename'	=> '',
				'page_filename'	=> '',
				'redirect' => ''
			);

			if(isset($_GET['par'])) {
				$rs['parent_id'] = intval($_GET['par']);
			}
		}
	}
}

$_TEMPLATE['PAGE_TITLE'] = 'Pages';

$_tmpl = getTemplate('update/pages/item.tmpl');

if($access) {
	
	
	$_tmpl->addVarList($rs);
	
	
	// "Advanced" tab
	debugPrint($rs, "Record");
//	$base->getPageOptions($rs['page_id'], '', 0, $rs['parent_id']);
	$_tmpl->addVar('pageOptions', $pageObj->getPageOptionsList($rs['page_id'], $rs['parent_id']));
	$options = array(
		'active'	=> 'Active',
		'inactive'	=> 'Inactive',
	);
	$_tmpl->addVar('statusOptionsList', $base->makeOptionsList($options, $rs['status']));
	$_tmpl->addVar('siteAssetOptionList', buildSiteAssetsList($db, $rs['asset']));
	$selectedGroupId = null;
	$_gObj = new group($db);
	$groupList = $_gObj->getAll_nvp();
	debugPrint($groupList, "Group list");
	$requiredGroupDisabled = null;
	if(is_array($rs) && count($rs) > 0) {
		$selectedGroupId = $rs['required_group_id'];
		
		
		$requiredGroupDisabled = $pageObj->getParentRequiredGroupId($rs['page_id']);
		debugPrint($requiredGroupDisabled, 'value for requiredGroupDisabled');
		
		if(intval($requiredGroupDisabled) > 0) {
			$_tmpl->addVar('requiredGroupDisabled', 'disabled');
			$_tmpl->addVar('parentGroupInfo', 'Value set on parent page, so this is disabled');
			$selectedGroupId = $requiredGroupDisabled;
		}
		
		if(intval($rs['media_id']) > 0) {
			$fileInfo = $mediaObj->get($rs['media_id']);
			debugPrint($fileInfo, "image file info");
			$_tmpl->addVarListWithPrefix($fileInfo, 'img_');
		}
		
		//leave "URL" blank if it's not the "clean" version of the title.
		if(page::makeCleanTitle($rs['title']) != $rs['url']) {
			$_tmpl->addVar('clean_url', $rs['url']);
		}
		
	}
	$_tmpl->addVar('requiredGroupOptionList', ToolBox::array_as_option_list($groupList, $selectedGroupId));
	
	
	// "Social" tab
	$_tmpl->addVar('adminMediaInput_ogimage', $base->adminMediaInput($rs['og_image_filename'], 'OG Image', 'ogimage', 'At least 1200x630px'));
	
	
	
	
	
	if(!is_array($rs) || $rs['page_id'] < 1 || $rs['asset'] !== 'home') {
		$_tmpl->setBlockRow('heroImage');
	}
	
	
	echo $_tmpl->render(true);
}
else {
	echo $acl->accessDeniedMsg();
}


function buildSiteAssetsList($db, $selectedAsset) {
	$retval = "";
	$sql = "SELECT
			a.name,
			a.clean_name,
			p.asset AS page_asset
		FROM assets a
		LEFT JOIN pages p ON a.name = p.asset
		WHERE a.visible = 1
		ORDER BY a.clean_name, a.name";
	$assets = $db->fetch_array($sql);
	foreach ($assets as $asset) {
		$retval .= '<option value="' . $asset['name'] . '" ';

		if($asset['name'] == $selectedAsset) {
			$retval .= ' selected="selected" ';
//		} else if($asset['page_asset'] == $asset['name']) {
//			$retval .= ' disabled="disabled" ';
		}
		$retval .= '>' . $asset['clean_name'] . '</option>';
	}
	
	return $retval;
}
