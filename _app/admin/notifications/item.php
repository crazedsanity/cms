<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\notification;

$_pageTmpl = getTemplate('update/notifications/item.tmpl');

$_TEMPLATE['PAGE_TITLE'] = "Add/Edit Notification";

$_obj = new notification($db);
$allTypes = $_obj->getTypesForOptionList();
$selectedType = null;

if(isset($_GET['notification_id']) && is_numeric($_GET['notification_id'])) {
	$data = $_obj->get($_GET['notification_id']);
	debugPrint($data, "Data for notification_id=(". $_GET['notification_id'] .")");
	$selectedType = $data['notification_type_id'];
	$data['isActive'. $data['is_active']] = " selected='selected'";
	$_pageTmpl->addVarList($data);
}
$_pageTmpl->addVar('typeOptionList', \Base::makeOptionsList($allTypes, $selectedType));


$_TEMPLATE['ADMIN_CONTENT'] = $_pageTmpl->render();