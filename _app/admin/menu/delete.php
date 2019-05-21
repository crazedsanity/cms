<?php

$user->superadmin = 1;
$user->restrict();

use crazedsanity\core\ToolBox;
//ToolBox::$debugPrintOpt = 1;

debugPrint($_POST, "POSTed data");
debugPrint($clean, "CLEAN DATA");



if(!empty($_POST) && intval($_POST['confirmation']) > 0 && $acl->access($_SESSION['MM_Username'], 'menu', $id, DELETE)) {
	if(intval($_POST['menu_item_id']) == $_POST['confirmation']) {
		$sql = "DELETE FROM menu_items WHERE menu_item_id=:id";
		$params = array('id'=>intval($_POST['menu_item_id']));
		$res = $db->run_query($sql, $params);
		
		addAlert("Result", "The menu item was deleted (". $res .")", 'notice');
	} elseif(intval($_POST['menu_id']) == $_POST['confirmation']) {
		$sql = "DELETE FROM menus WHERE menu_id=:id";
		$params = array('id'=>intval($_POST['menu_id']));
		$res = $db->run_query($sql, $params);
		
		addAlert("Result", "The menu was deleted (". $res .")", 'notice');
	} 
	else {
		addAlert("Failure", "Invalid information, could not delete record", "error");
	}
}
else {
	addAlert("Error", 'Error: No menu or menu item was selected to be deleted.', 'error');
}


$goHere = '/update/menu/index.php';
debugPrint($sql, "the sql");
debugPrint($params, "parameters");
debugPrint($res, "result");
if(!debugPrint("<a href='{$goHere}'>{$goHere}</a>", "You're debugging... so here's the URL")) {
	crazedsanity\core\ToolBox::conditional_header($goHere);
}
exit;
