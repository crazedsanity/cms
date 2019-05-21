<?php
$user->superadmin = 1;
$user->restrict();

$sql = "SELECT * FROM groups ORDER BY name ASC";
$groups = $db->fetch_array($sql);


$_TEMPLATE['PAGE_TITLE'] = 'Groups';

$_tmpl = getTemplate('update/accounts/groups/index.tmpl');
$_TEMPLATE['keyName'] = 'group_id';

$csum = time();
$_SESSION['csum'] = $csum;
$_tmpl->addVar('csum', $csum);

$row = $_tmpl->setBlockRow('row');

if(!$acl->access($_SESSION['MM_Username'], 'accounts', 0, ADD)) {
	$_tmpl->addVar('addNewGroupLink', 'hidden');
}

// TODO: either make this access check valid (so a user can be given/denied access to/from a group) or remove it altogether.
foreach($groups as $key=>$group) {
	if(!$acl->access($_SESSION['MM_Username'], 'accounts', $group['group_id'], DELETE)) {
		$groups[$key]['deleteGroupLink'] = 'hidden';
	}
	if(!$acl->access($_SESSION['MM_Username'], 'accounts', $group['group_id'], EDIT)) {
		$groups[$key]['ditGroupLink'] = 'hidden';
	}
}

$_tmpl->addVar($row->name, $row->renderRows($groups));

$_TEMPLATE['ADMIN_CONTENT'] = $_tmpl->render();
