<?php

use cms\cms\core\page;
use crazedsanity\core\ToolBox;

//ToolBox::$debugPrintOpt = 1;

$assetTmpl = getTemplate('assets/search.html');

$numResults = 0;
if(isset($_GET['q'])) {
	debugPrint($_GET, "URL parameters");
	$pageObj = new page($db);
	
	try {
		$row = $assetTmpl->setBlockRow('resultRow');
		$query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
		$searchResults = $pageObj->searchPages($query);
		
		$numResults = count($searchResults);
		
		debugPrint($searchResults, "page search results");
		
		$assetTmpl->addVar('query', $query);
		if(count($searchResults) > 0) {
			$assetTmpl->addVar($row->name, $row->renderRows($searchResults));
		}
	}
	catch(Exception $ex) {
		debugPrint($ex->getMessage(), "Exception caught");
	}
	
}

$assetTmpl->addVar('numResults', $numResults);


