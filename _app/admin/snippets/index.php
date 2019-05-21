<?php


use crazedsanity\core\ToolBox;
use cms\cms\core\snippet;
//ToolBox::$debugPrintOpt = 1;



$_TEMPLATE['PAGE_TITLE'] = 'Snippets';
$_TEMPLATE['keyName'] = 'snippet_id'; 

$_obj = new snippet($db);

if(isset($_POST) && count($_POST) > 0) {
//	define("DEBUGPRINT", true);
	addAlert("Invalid Action", "The requested action was invalid", "error");
	ToolBox::conditional_header('/update/snippets/');
	exit;
}
else {
	$_mainTmpl = getTemplate("/update/snippets/index.tmpl");
	$row = $_mainTmpl->setBlockRow('row');
	
	$data = $_obj->getAll();
	debugPrint($data, "all snippets");
	try {
		$_mainTmpl->addVar($row->name, $row->renderRows($data));
	}
	catch(Exception $ex) {
		
	}

	echo $_mainTmpl->render(true);
	
}
