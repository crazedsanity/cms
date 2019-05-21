<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\snippet;



if(isset($_POST['snippet_id']) && intval($_POST['snippet_id']) > 0) {
	try {
		$_obj = new snippet($db);
		$delRes = $_obj->delete(intval($_POST['snippet_id']));
		addAlert("Snippet Deleted", "Record ID #". intval($_POST['snippet_id']) ." has been deleted (". $delRes .")", "notice");
	}
	catch (Exception $ex) {
		addAlert("Error Entered", "There was an error: ". $ex->getMessage, "fatal");
	}
}
else {
	addAlert("Missing ID", "Your request was missing an ID", "error");
}

ToolBox::conditional_header('/update/snippets/');
exit;
