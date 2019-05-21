<?php

include( 'homeandinside.php' );
$_TEMPLATE['HEADER_BG_IMAGE_NUM'] = '1';

use crazedsanity\core\ToolBox;
use cms\cms\core\notification;
use cms\cms\core\gallery;
use cms\cms\core\galleryPhotos;
use cms\cms\core\calendar;
use \cms\cms\core\media;
//ini_set('display_errors', true);
//ToolBox::$debugPrintOpt = 1;

/*
 * Put stuff below here that applies only to the homepage.
 */


// Show a news item.
try {
	$newsItem = $pageTmpl->setBlockRow('newsItem');
	
	$defaultImageLink = '/_elements/images/news-default-image.jpg';
	
	$newsObj = new cms\cms\core\news($db);
	$data = $newsObj->getAll(null, array('front_page' => 1, 'approved' => 1));
	debugPrint($data, "news data");
	
	if(count($data) > 0) {
		$myData = $data[array_keys($data)[0]];
		
		
		$previewBits = explode('</p>', $myData['description'],2);
		$myData['description'] = $previewBits[0] .'</p>';
		
		$newsItem->addVarList($myData);
		if(empty($myData['filename'])) {
			$newsItem->addVar('filename', $defaultImageLink);
		}
		else {
			$newsItem->addVar('filename', '/data/upfiles/media/'. $myData['filename']);
		}
		
		$pageTmpl->addVar($newsItem->name, $newsItem->render());
	}
} 
catch (Exception $ex) {
	debugPrint($ex, "Exception");
//	exit;
}


// pull stuff from the blog cache
//ToolBox::$debugPrintOpt = 1;
try {
	$blogRow = $pageTmpl->setBlockRow('blogRow');
	$blogData = $db->fetch_array("SELECT * FROM blog_cache ORDER BY pubDate DESC limit 4");
	debugPrint($blogData, "blog data");

	foreach($blogData as $k=>$v) {
		$wordLimit = 16;
		$avgWordSize = 7;
		$charLimit = ($wordLimit * $avgWordSize);
		$blogData[$k]['description'] = ToolBox::truncate_string(strip_tags($v['description']), $charLimit);
		
		$blogData[$k]['displayDate'] = date('D, F jS, Y @ H:i a', strtotime($v['pubDate']));
	}
	$renderedBlog = $blogRow->renderRows($blogData);
	debugPrint(htmlentities($renderedBlog), "rendered blog rows");
	$pageTmpl->addVar($blogRow->name, $renderedBlog);
//	$_TEMPLATE['BLOG'] .= $renderedBlog;
}
catch(Exception $ex) {
	applicationLog('home', "Error pulling blog cache::: ". $ex->getTraceAsString());
}


// show most recent notification.
try {
	$alert = $pageTmpl->setBlockRow('alert');
	
	$nObj = new notification($db);
	$nData = $nObj->getMostRecent();
	
	if(is_array($nData) && intval($nData['notification_id']) > 0) {
		$alert->addVarList($nData);
		$pageTmpl->addVar($alert->name, $alert->render());
	}
}
catch (Exception $ex) {

}


//// show gallery.
////ToolBox::$debugPrintOpt = 1;
//try {
//	$galRow = $pageTmpl->setBlockRow('galleryImage');
//	$gObj = new gallery($db);
//	
//	$all = $gObj->getAll(null, array('name'=>'homepage'));
//	debugPrint($all, "galleries");
//	
//	if(is_array($all) && count($all) == 1) {
//		$theGallery = $all[array_keys($all)[0]];
//		debugPrint($theGallery, "gallery data");
//		$pObj = new galleryPhotos($db, $theGallery['gallery_id']);
//		
//		$galPhotos = $pObj->getAll();
//		debugPrint($galPhotos, "gallery photos");
//		
//		$pageTmpl->addVar($galRow->name, $galRow->renderRows($galPhotos));
//	}
//	
////	exit(__FILE__ ." - line #". __LINE__);
//} catch (Exception $ex) {
//	addAlert("Gallery Problem", "ERROR: ". $ex->getMessage(), "error");
//}


// Add calendar styles
try {
	$calObj = new calendar($db);
	$pageTmpl->addVar('CALENDARSTYLES', $calObj->generateCalStyles());
	
	$calNameRow = $pageTmpl->setBlockRow('selectCalendar');
	$allCals = $calObj->getAll();
	$renderedCalList = $calNameRow->renderRows($calObj->getAll());
	$pageTmpl->addVar($calNameRow->name, $renderedCalList);

} catch (Exception $ex) {
	// couldn't generate styles?  Too bad...
}



// set a hero image, if possible.
$pageTmpl->addVar('heroImagePath', '/images/slider/swiper/1.jpg');
try {
	$mediaObj = new media($db);
	if(isset($page['media_id']) && intval($page['media_id']) > 0) {
		$heroInfo = $mediaObj->get($page['media_id']);
		
		if(is_array($heroInfo) && count($heroInfo)) {
			$thePath = $heroInfo['path'] . $heroInfo['filename'];
			if(file_exists(ROOT . $thePath)) {
				$pageTmpl->addVar('heroImagePath', $thePath);
			}
		}
	}
} catch (Exception $ex) {
	// 
}

	//EVENTS
	
	$sql=<<<EOSQL
	SELECT title, start, end, short_description, url(title) AS clean, url(sub_category) as clean_cat, url(category) as cleanother
	FROM places p
	WHERE  
		p.category='Do' AND sub_category='Events'
		AND (DATE_FORMAT(NOW(), start) <= DATE_FORMAT(NOW(), '%Y-%m-%d') OR DATE_FORMAT(NOW(), end) >= DATE_FORMAT(NOW(), '%Y-%m-%d'))
		AND p.active = 1
	ORDER BY start ASC
	LIMIT 4
EOSQL;
	
	$events = $db->fetch_array($sql);
	
	
	foreach($events as $event){
	
		$start = date('F j', strtotime($event['start']));
		$end = date('F j', strtotime($event['end']));
	
	
		if($start == $end){
			$end ='';
		}
		else if($end){
			$end = ' - '.$end;
		}
	
		$_TEMPLATE['EVENTS'].=<<<CONTENT
	
	<div class="eventlistitem">
				<h4><a href="/{$event['cleanother']}/{$event['clean_cat']}/{$event['clean']}">{$event['title']}</a></h4>
				<span>{$event['short_description']}</span>
				<p>{$start}{$end}</p>
			</div>
			<div class="separator"></div>
	
	
CONTENT;
	}
	
	
	$_TEMPLATE['EVENTS'].='here';
	
	
