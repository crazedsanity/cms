<?php 

use crazedsanity\core\ToolBox;

if($clean['news_id']){
	$news_id = $clean['news_id'];
}
else{
	$news_id = 0;	
}

$access=false;
if($acl->access($_SESSION['MM_Username'], 'news', $news_id, DELETE)){
	$access=true;
}

if($access){
	if (isset($clean['news_id'])) {
		$newsObj = new cms\news($db);
		$res = $newsObj->delete($clean['news_id']);
		addAlert("Article Deleted", "The article was deleted ({$res})", "notice");
	}
	else {
		addAlert("Failure", "Not enough information given to delete article", "error");
	}
}
else {
	addAlert("Access Denied", "You don't have permission to do that.", "error");
}

ToolBox::conditional_header($sectionUrl);
exit;
