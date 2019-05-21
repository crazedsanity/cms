<?php
$user->superadmin = 1;
$user->restrict();

use crazedsanity\core\ToolBox;

//ToolBox::$debugPrintOpt=1;

$admins = $user->getAll("group_id DESC, company, username, name");


$byGroup = array();
foreach($admins as $k=>$v) {
	$byGroup[$v['group_id']][$k] = $v;
}
debugPrint($byGroup, "users by group");

$_tmpl = getTemplate('update/accounts/index.tmpl');
$_TEMPLATE['keyName'] = 'user_id';

$csum = time();
$_SESSION['csum'] = $csum;
$_tmpl->addVar('csum', $csum);

$_TEMPLATE['PAGE_TITLE'] = 'Accounts';



if(!$acl->access($_SESSION['MM_Username'], 'accounts', 0, ADD)) {
	$_tmpl->addVar('addNewAccountLink', 'hidden');
}
$userRow = $_tmpl->setBlockRow('user');
$groupRow = $_tmpl->setBlockRow('group');


$rendered = "";
foreach($byGroup as $gIdx => $userList) {
	$keys = array_keys($userList);
	$groupRow->addVarList($userList[$keys[0]]);
	
	$groupRow->addVar($userRow->name, $userRow->renderRows($userList));
	$rendered .= $groupRow->render();
	$groupRow->reset();
}

$_tmpl->addVar($groupRow->name, $rendered);


// put the rendered content of this page into a template var for later.
$_TEMPLATE['ADMIN_CONTENT'] = $_tmpl->render(true);