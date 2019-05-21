<?php


use crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt = 1;


$newsObj = new \cms\news($db);


if($_POST) {
	\debugPrint($_POST, "Post data");

	if(isset($_POST['selected_items']) && count($_POST['selected_items'])) {
		$results = 0;


		$selectedTags = array();
		if(isset($_POST['tags']) && count($_POST['tags']) > 0) {
			$selectedTags = $_POST['tags'];
		}
		if(isset($_POST['newtags']) && !empty($_POST['newtags'])) {
			$bits = explode(',', $_POST['newtags']);
			foreach ($bits as $v) {
				$newTag = trim($v);
				if(!empty($newTag)) {
					$selectedTags[] = $newTag;
				}
			}
		}
		debugPrint($selectedTags, "Selected tags");
		if(count($selectedTags) > 0) {
			foreach ($_POST['selected_items'] as $newsId => $garbage) {
				$results += $newsObj->addTags($newsId, $selectedTags);
			}
		}

		$removeTags = array();
		if(isset($_POST['removetags']) && !empty($_POST['removetags'])) {
			$removeTags = explode(',', $_POST['removetags']);
		}

		if(count($selectedTags) > 0 || count($removeTags) > 0) {

			if(count($selectedTags)) {
				foreach ($_POST['selected_items'] as $mediaId => $junk) {
					$results += $newsObj->addTags($mediaId, $selectedTags);
				}
			}
			if(count($removeTags)) {
				$tagObj = new \cms\tag($db);
				foreach ($_POST['selected_items'] as $mediaId => $junk) {
					$results += $tagObj->deleteTags($mediaId, 'news', $removeTags);
				}
			}
		}

		//	exit;
		$location = $_SERVER['PHP_SELF'] . "?alert=" . urlencode("Tags applied, " . $results . " tags added/deleted");
		debugPrint($db->history, "DB History");
	} else {
		$location = $_SERVER['PHP_SELF'] . "?alert=" . urlencode("No items were selected for tagging.");
	}
	if(!debugPrint("<a href='{$location}'>{$location}</a>", "looks like you're debugging, so here's where you woulda been redirected to")) {
		ToolBox::conditional_header($location);
	}
	exit;

} else {
	$_TEMPLATE['keyName'] = 'news_id';
	$articles = $newsObj->getAll();

	//templates.
	$_mainTmpl = getTemplate('update/news/index.tmpl');
	$_pageOptions = $_mainTmpl->setBlockRow('pageOptions');
	$_rowOptionEdit = $_mainTmpl->setBlockRow('rowOption_edit');
	$_rowOptionDelete = $_mainTmpl->setBlockRow('rowOption_delete');
	$_rowApproved = $_mainTmpl->setBlockRow('rowApproved');
	$_rowTmpl = $_mainTmpl->setBlockRow('newsRow');

	//add options, if they have access (the template already knows it's place, so it can just be added).
	if($acl->access($_SESSION['MM_Username'], 'news', 0, ADD)) {
		$_mainTmpl->add($_pageOptions);
	}

	$renderedRows = "";
	foreach ($articles as $article) {

		$_rowTmpl->addVarList($article);


		$_rowTmpl->addVar('rowApproved', '');
		if($article['approved'] == '1') {
			$_rowTmpl->add($_rowApproved);
		}
		if($acl->access($_SESSION['MM_Username'], 'news', $article['news_id'], EDIT)) {
			$_rowOptionEdit->addVarList($article);
			$_rowTmpl->add($_rowOptionEdit);
		}
		if($acl->access($_SESSION['MM_Username'], 'news', $article['news_id'], DELETE)) {
			$_rowOptionDelete->addVarList($article);
			$_rowTmpl->add($_rowOptionDelete);
		}

		$renderedRows .= $_rowTmpl->render();
	}

	$_mainTmpl->addVar($_rowTmpl->name, $renderedRows);

	echo $_mainTmpl->render(true);
}