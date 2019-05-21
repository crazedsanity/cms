<?php


use crazedsanity\core\ToolBox;
use cms\cms\core\snippet;

//ToolBox::$debugPrintOpt = 1;

$_TEMPLATE['PAGE_TITLE'] = 'Snippets';
$_TEMPLATE['keyName'] = 'snippet_id'; 

$goHere = '/update/snippets/';

$_obj = new snippet($db);


if(!empty($_POST)) {
	
	if(isset($_POST['action'])) {
		if(isset($_POST['snippet']) && is_array($_POST['snippet']) && count($_POST['snippet']) > 0) {
			if($_POST['action'] == 'add') {
				try {
					$newId = $_obj->insert($_POST['snippet']);
					addAlert("Record Created", "Snippet ID #". $newId ." was created successfully.", "notice");
				} 
				catch (Exception $ex) {
					addAlert("Database Error", $ex->getMessage(), "fatal");
				}
			}
			elseif($_POST['action'] == 'edit') {
				if(isset($_POST['snippet_id']) && intval($_POST['snippet_id']) > 0) {
					try {
					$updateRes = $_obj->update($_POST['snippet'], intval($_POST['snippet_id']));
					addAlert("Update Successful", "The record was updated successfully (". $updateRes .")", "notice");
					}
					catch(Exception $ex) {
						addAlert("Database Error", $ex->getMessage(), "fatal");
					}
				}
				else {
					addAlert("Missing ID", "Your request was missing an ID", "error");
				}
			}
			else {
				addAlert("Invalid Action", "Your request was missing an action.", "error");
			}
		}
		else {
			addAlert("Missing Data", "Your request was missing some required information.", "error");
		}
	}
	else {
		addAlert("Missing Action", "Your request was missing an action.", "error");
	}
	
	if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "Was gonna redirect, but you're debugging, so here's a link instead")) {
		ToolBox::conditional_header($goHere);
	}
	exit;
}
else {
	$error = false;
	$_tmpl = getTemplate('update/snippets/item.tmpl');
	if(isset($_GET['action'])) {
		$_tmpl->addVar('action', $clean['action']);
		if($_GET['action'] == 'edit') {
			if(isset($_GET['snippet_id']) && intval($_GET['snippet_id']) > 0) {
				$data = $_obj->get(intval($_GET['snippet_id']));
				if(is_array($data) && count($data) > 0) {
					try {
						$data = $_obj->get(intval($_GET['snippet_id']));
						debugPrint($data, "data");
						
						// fix description to be input-freindly.
						$data['description'] = htmlentities($data['description']);
						
						$_tmpl->addVarList($data);
					}
					catch (Exception $ex) {
						$error = true;
						addAlert("Error Encountered", "An error occurred trying to retrieve the data: ". $ex->getMessage, "fatal");
					}
				}
				else {
					addAlert("No Data", "Could not find a record matching your request.  Please try again.", "error");
					$error = true;
				}
			}
			else {
				addAlert("Missing ID", "Your request was missing an ID", "error");
				$error = true;
			}
		}
		elseif($_GET['action'] == 'add') {
			
		}
		else {
			$error = true;
			addAlert("Invalid Action", "The requested action was invalid. Please try again.", "error");
		}
	}
	else {
		$error = true;
		addAlert("Missing Action", "Your request was missing an action.", "error");
	}
	
	if($error === true) {
		debugPrint($_SESSION, "Session");
		if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "There was an error, but you're debugging, so no redirection")) {
			ToolBox::conditional_header($goHere);
		}
		exit;
	}
	echo $_tmpl->render(true);
}

