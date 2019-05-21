<?php

$user->superadmin = 1;
$user->restrict();

if(intval($_POST['page_id'])) {
	$page_id = $clean['page_id'];
}

if(isset($_POST['page_id']) && intval($_POST['page_id']) == $_POST['confirmation'] && $acl->access($_SESSION['MM_Username'], 'pages', $page_id, DELETE)) {
	if(isset($_POST['page_id'])) {
		$sql = "DELETE FROM pages WHERE page_id=" . intval($_POST['page_id']);
		$res = $db->query($sql);
		
		addMsg("Success", "Page deleted successfully (". $res .")");
		$base->setSiteMapXML();
	}
	else {
		addMsg("Fail", "Unable to delete, no ID found", "error");
	}
}
else {
	addMsg("Access Denied", "You do not appear to have sufficient access to delete this page", "error");
}


crazedsanity\core\ToolBox::conditional_header('/update/pages/index.php');
exit;
