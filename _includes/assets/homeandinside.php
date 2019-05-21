<?php


use crazedsanity\core\ToolBox;
use crazedsanity\messaging\Message;
use cms\cms\core\snippet;
use cms\cms\core\page;


// For testing (ensuring styles are good for alerts)
if(!empty($_GET['testalert'])) {
	$types = array(Message::TYPE_FATAL, Message::TYPE_ERROR, Message::TYPE_STATUS, Message::TYPE_NOTICE);
	if(in_array($_GET['testalert'], $types)) {
		$type = $_GET['testalert'];
		addAlert("Test Alert (". $type .")", "This is a test alert, of type '". $type ."'... to see 'em all, click <a href='?testalert=1'>here</a>", $type);
	}
	else {
		foreach($types as $type) {
			addAlert("Test Alert (". $type .")", "This is a test alert, of type '". $type ."'... to see just this one, click <a href='?testalert=".$type."'>here</a>", $type);
		}
	}
}


/*
 * Load any available snippets.
 */
try {
	$_snipObj = new snippet($db);
	$allSnips = $_snipObj->getAll();
	debugPrint($allSnips, "All snippets");
	
	foreach($allSnips as $k=>$v) {
		$theCode = strtoupper(page::makeCleanTitle($v['code']));
		$_TEMPLATE['SNIPPET__'. $theCode] = $v['body'];
	}

}
catch(Exception $ex) {
	// TODO: log this somewhere.
}