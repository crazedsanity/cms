<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\notification;



$_TEMPLATE['PAGE_TITLE'] = 'Notifications';

$_tmpl = getTemplate('update/notifications/index.tmpl');

$notifications = new notification($db);

$records = $notifications->getAll();

debugPrint(print_r($records,true), "All Notifications");

$csum = time();
$_SESSION['csum'] = $csum;



//$_tmpl->addVar('keyName', 'notification_id');
$_TEMPLATE['keyName'] = 'notification_id';

$myRow = $_tmpl->setBlockRow('row');
if(count($records)) {
	debugPrint($records, "notification records");
	//remove the "no data" row
	$_tmpl->setBlockRow('noData');
	
	
	//forge in some things
	foreach($records as $i=>$data) {
		
		$data['preview'] = ToolBox::truncate_string(strip_tags($data['body']), 20);
		$data['CHECKTIME'] = $csum;
		
		$statusClass = 'active';
		if($data['is_active'] != '1') {
			$statusClass = 'inactive';
		}
		$data['statusClass'] = $statusClass;
		
		//check for access
		if(!$acl->access($_SESSION['MM_Username'], 'notifications', $i, EDIT)) {
			$data['editHidden'] = 'invisible';
		}
		
		
		//check for access
		if(!$acl->access($_SESSION['MM_Username'], 'notifications', $i, DELETE)) {
			$data['deleteHidden'] = 'invisible';
		}
		
		$records[$i] = $data;
	}
	
	$parsedRows = $myRow->renderRows($records);
	$_tmpl->addVar('row', $parsedRows);
}


//echo $_tmpl->render();

$_TEMPLATE['ADMIN_CONTENT'] = $_tmpl->render();
