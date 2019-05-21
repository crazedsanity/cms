<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\assets;


$assetObj = new assets($db);


//ToolBox::$debugPrintOpt = 1;
if(debugPrint("turning on display_errors")) {
	ini_set('display_errors', true);
}


$goHere = "/update/assets/";

if(!empty($_POST)) {
	
	debugPrint($_POST, "POSTed data");
	
	if(!empty($_POST['action']) && in_array($_POST['action'], array('add', 'edit'))) {
		
		try {
			if($_POST['action'] == 'add') {
				$addRes = $assetObj->insert($_POST['asset']);
				addAlert("Record Created", "The record was created successfully (". $addRes .")");
			}
			elseif($_POST['action'] == 'edit') {
				$updateRes = $assetObj->update($_POST['asset'], intval($_POST['asset_id']));
				addAlert("Record Updated", "The record was updated succesfully (". $updateRes .")");
			}
		}
		catch(Exception $ex) {
			addAlert("Exception Encountered", "The following error was encounted (YOU SHOULD REPORT THIS): ". 
					$ex->getMessage() ."<br>". $ex->getTraceAsString(),
					"fatal"
				);
		}
		
	}
	else {
		addAlert("Invalid Action", "The given action was missing or invalid. Maybe you clicked an old link?", "error");
	}
	
	
	
	if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "No redirection, because you're debugging; here's a link")) {
		ToolBox::conditional_header($goHere);
	}
	exit;
}

$tmpl = getTemplate('update/assets/item.html');


if(isset($_GET['action']) && in_array($_GET['action'], array('add', 'edit'))) {
	$tmpl->addVar('action', $_GET['action']);
	if($_GET['action'] === 'edit') {
		$error = false;
		if(intval($_GET['asset_id']) > 0) {
			$record = $assetObj->get($_GET['asset_id']);
			if(is_array($record) && count($record) > 0) {
				debugPrint($record, "asset record");
				
				$tmpl->addVarList($record);
				
				$theVar = 'visible__'. intval($record['visible']);
				$tmpl->addVar($theVar, 'selected');
				
			}
			else {
				$error = true;
			}
		}
		
		if($error) {
			addAlert("Invalid Record", "That record does not exist. Maybe you clicked an old link?", "error");
			if(!debugPrint($_SESSION['messages'], "Invalid record... but not redirecting you.")) {
				ToolBox::conditional_header('/update/');
			}
			exit(__FILE__ ." - line #". __LINE__);
		}
	}
	
}
else {
	addAlert("Invalid Action", "The given action was missing or invalid. Maybe you clicked an old link?", "error");
	if(!debugPrint($_SESSION['messages'], "Invalid action... but not redirecting you.")) {
		ToolBox::conditional_header('/update/');
	}
	exit;
}


echo $tmpl->render();

