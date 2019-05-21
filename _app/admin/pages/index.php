<?php
//$user->superadmin = 1;
//$user->restrict();


use crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt = 1;


$_mainTmpl = getTemplate("/update/pages/index.tmpl");

$_row = $_mainTmpl->setBlockRow('pageRow');
//$_row = getTemplate("/update/pages/pageRow.tmpl");

function parseRow($depth, $record) {
	global $acl;
	global $_row;

	$id = $record['page_id'];

	
	$record['pageIcons'] = '';
	$record['pageIconDescription'] = '';
	if(intval($record['_required_group_id']) > 0 || intval($record['required_group_id']) > 0) {
		debugPrint($record, __METHOD__ ." - adding icons, record data");
		$record['pageIcons'] = 'fa fa-lock';
		$record['pageIconDescription'] = 'Requires group membership, see parent record.';
		if(isset($record['required_group_name'])) {
			$record['pageIconDescription'] = 'Page requires '. $record['required_group_name'] .' group membership';
		}
	}

	//add template vars based on column names.
	$record['class'] = '';
	if($record['status'] != 'active') {
		$record['class'] = 'inactive';
	}
	$_row->addVarList($record);

	//add an indent.
	$indentString = str_repeat("&nbsp;&nbsp;-&nbsp;&nbsp;", $depth);
	$_row->addVar("indent", $indentString, false);

	//add options for this page.
	$pageOptions = "";
	$_row->addVar("hideEdit", "hidden");
	if($acl->access($_SESSION['MM_Username'], 'pages', $id, EDIT)) {
		$_row->addVar("hideEdit", "");
	}
	
			
	
	$_row->addVar("hideDelete", "hidden");
	if($acl->access($_SESSION['MM_Username'], 'pages', $id, DELETE) && $record['_num_children'] == 0) {
		$_row->addVar("hideDelete", "");
	}
	$_row->addVar("pageOptions", $pageOptions, false);

	//add a link for sorting, if there's something to sort.
	$sortHtml = "";
	$_row->addVar("hideSort", "hidden");
	if($record['_num_children'] > 0) {
		$_row->addVar("hideSort", "");
	}
	$_row->addVar("sorthtml", $sortHtml, false);
	$out = preg_replace('/%%page_id%%/', $record['page_id'], $_row->render(false));


	//add a placeholder for sub-items.
	return $out;
}

function displayPages($_mainTmpl) {
	global $db;

	$pageObj = new \cms\page($db);

	$allPages = $pageObj->getAll();

	$sorted = array();

	$maxDepth = 1;
	$minDepth = 9;
	foreach ($allPages as $id => $data) {
		$sorted[$data['_depth']][$id] = $data;
		if($data['_depth'] > $maxDepth) {
			$maxDepth = $data['_depth'];
		}
		if($data['_depth'] < $minDepth) {
			$minDepth = $data['_depth'];
		}
	}

	// now re-order things (probably easier with array_multisort()) by the parent_id
	$magicList = array();
	foreach ($sorted as $depth => $recordList) {
		foreach ($recordList as $id => $data) {
			unset($data['body']);
			$magicList[$data['parent_id']][$id] = $data;
		}
	}

	//okay, now we can start cooking with Crisco.  Or gasoline.  Or maybe knives.
	$renderedPage = "";
	$_finalPage = getTemplate("update/cheater.tmpl");
	

	foreach ($magicList as $parentId => $levelOne) {
		$allChildRows = "";
			
			
		foreach ($levelOne as $record) {
			$depth = $record['_depth'];
			$allChildRows .= parseRow($depth, $record);
		}
		
		

			
		if($parentId == 0) {
			//nothing to parse this into, it becomes the entire content.
			$_finalPage->addVar("content", $allChildRows, false);
			$renderedPage = $_finalPage->render();
		} else {
			$_finalPage->addVar("html_child_" . $parentId, $allChildRows, false);
			$renderedPage = $_finalPage->render(false);
		}
	}

	$_mainTmpl->addVar("pageRow", $renderedPage);
	return $renderedPage;
}


$_TEMPLATE['PAGE_TITLE'] = 'Pages';
$_TEMPLATE['keyName'] = 'page_id';

if(isset($_POST) && count($_POST) > 0) {
//	define("DEBUGPRINT", true);
	
	$pageObj = new \cms\page($db);
	
	\debugPrint($_POST, "Post data");
	if(is_array($_POST['tags']) && isset($_POST['in']['tags'])) {
		$selectedTags = explode(',', $_POST['in']['tags']);
		
		
		\debugPrint($selectedTags, "Selected tags");
		$results = 0;
		foreach($_POST['tags'] as $pageId=>$garbage) {
			$results += $pageObj->addTags($pageId, $selectedTags);
		}
	}
	
//	exit;
	$location = $_SERVER['PHP_SELF'] ."?alert=". urlencode("Tags applied, ". $results ." tags added/created");
	\crazedsanity\core\ToolBox::conditional_header($location);
	exit;
}
else {
//	include(ROOT . '/update/_includes/meta.php');
//	include(ROOT . '/update/_includes/header.php');
//	include(ROOT . '/update/_includes/menu.php');

//	echo!empty($_GET['alert']) ? '<span class="alert">' . htmlentities($_GET['alert']) . '</span>' : '';

//	$_mainTmpl = getTemplate('update/pages/index.tmpl');


	$renderedPageListing = displayPages($_mainTmpl);

//	$_mainTmpl->addVar("pageRow", $renderedPageListing);

	if(!$acl->access($_SESSION['MM_Username'], 'pages', 0, ADD)) {
//		$_mainTmpl->add(getTemplate('update/pages/main_options.tmpl', "mainOptions"));
		$_mainTmpl->addVar("mainOptionsClass", "hidden");
	}

	echo $_mainTmpl->render(true);
	
}
