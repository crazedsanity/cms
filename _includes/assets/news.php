<?php

use cms\cms\core\news;
use crazedsanity\core\ToolBox;
use cms\database\constraint;

//ToolBox::$debugPrintOpt = 1;

if(debugPrint('displaying errors...')) {
	ini_set('display_errors', true);
}


$newsObj = new news($db);

$defaultImageLink = '/_elements/images/news-default-image.jpg';
$defaultImageThumb = '/_elements/images/news-default-image_thumb.jpg';


debugPrint($urls, "URL data");


if(count($urls) > 1) {
	 if($urls[1] == 'view' && intval($urls[2]) > 0) {
		 $assetTmpl = getTemplate('assets/news-item.html');
		 $article = $newsObj->get(intval($urls[2]));
		 
		 debugPrint($article, "News article");
		 
		 if(is_array($article) && count($article) > 0) {
			if(empty($article['filename'])) {
				$article['filename'] = $defaultImageLink;
			}
			else {
				$article['filename'] = '/_elements/thumb.php?x=200&y=140&i='. $article['filename'];
			}
			$assetTmpl->addVarList($article);
		 }
		 else {
			 addAlert("Invalid Article", "The article you attempted to view was invalid. Maybe it was an old link?", "status");
			 ToolBox::conditional_header("/news/");
			 exit;
		 }
	 }
	 else {
		 // Invalid URL.
		 addAlert("Invalid Article", "The article you attempted to view was invalid. Maybe it was an old link?", "status");
		 ToolBox::conditional_header("/news/");
		 exit;
	 }
}
else {
	$assetTmpl = getTemplate('assets/news.html');
	$newsRow = $assetTmpl->setBlockRow('row');
	
	
	$articles = $newsObj->getAll(null, array(
		'approved'		=> 1,
		'start_date'	=> new constraint('date', '<=', "CURRENT_TIMESTAMP"),
		'end_date'		=> new constraint('date', '>', "CURRENT_TIMESTAMP"),
	));
	debugPrint($articles, "All news articles");



	foreach($articles as $k=>$v) {
		if(!empty($v['filename']) && file_exists(MEDIA_DIR .'/'. $v['filename'])) {
			$articles[$k]['filename'] = '/_elements/thumb.php?x=200&y=140&i='. $v['filename'];
		}
		else {
			$articles[$k]['filename'] = $defaultImageThumb;
			debugPrint($v, "filename missing, or file does not exist");
		}
//		$articles[$k]['description'] = '';
		
		$dateBits = explode(' ', $v['start_date']);
		$formattedDate = date('l, F jS, Y', strtotime($v['start_date']));
		
		debugPrint($dateBits, "date bits");
		if($dateBits[1] !== '00:00:00') {
			$formattedDate .= ' '. date('g:i A', strtotime($v['start_date']));
		}
		$articles[$k]['date'] = $formattedDate;
	}
	$assetTmpl->addVar($newsRow->name, $newsRow->renderRows($articles));

}
