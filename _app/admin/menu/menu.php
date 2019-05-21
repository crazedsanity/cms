<?php
$user->restrict();
if(isset($clean['menu_id'])) {
	$menu_id = intval($clean['menu_id']);
} else {
	$menu_id = 0;
}

$access = false;
if($menu_id > 0 && ( $acl->access($_SESSION['MM_Username'], 'menu', $menu_id, EDIT) )) {
	$access = true;
} else if($menu_id <= 0 && ( $acl->access($_SESSION['MM_Username'], 'menu', $menu_id, ADD) )) {
	$access = true;
}

if($access) {
	if(isset($clean['menu']["name"])) {
		$title = "Success";
		// Save Page
		if(isset($clean['menu_id']) && $clean['menu_id'] != 0) {
			// Update
			$db->update("menus", $clean["menu"], "menu_id=" . $menu_id);
			$alert = "Record has been updated";
			$location = "/update/menu/menu.php?menu_id=" . $menu_id;
		} else {
			// Insert
			$page_id = $db->insert("menus", $clean["menu"]);
			$alert = "Record has been added";
			$location = "/update/menu/index.php";
		}
		addMsg($title, $alert);
		crazedsanity\core\ToolBox::conditional_header($location);
		exit;
	} else {
		if(isset($clean['menu_id'])) {
			$menu_id = intval($clean['menu_id']);
			$sql = "SELECT * FROM menus WHERE menu_id=" . $menu_id;
			$rs = $db->query_first($sql);
		} else {
			$menu_id = 0;
		}
	}
}


echo!empty($_GET['alert']) ? '<div class="success">' . htmlentities($_GET['alert']) . '</div>' : '';
?>
<script>
	$(document).ready(function () {
		$('#return').button({icons: {primary: "ui-icon-arrowreturnthick-1-w"}});
	});
</script>

<p><a href="/update/menu/index.php" id="return">View All</a></p>

<?php

$_TEMPLATE['PAGE_TITLE'] = 'Add/Edit Menu';

if($access) {
	if(isset($_GET['menu_id']) && !isset($rs['menu_id'])) {
		$rs['menu_id'] = intval($_GET['menu_id']);
	}
	?>
	<form method="POST" enctype="multipart/form-data">
		<div id="tabs">
			<ul>
				<li><a href="#tab-1"><span>General</span></a></li>
			</ul>
			<div id="tab-1">
				<p>
					<label for="title">Title</label> <br>
					<input type="text" class="title" name="menu[name]" value="<?php echo isset($rs['name']) ? htmlentities($rs['name']) : ''; ?>">
				</p>
				<p>
					<label for="title">CSS Class (optional)</label> <br>
					<input type="text" class="title" name="menu[class]" value="<?php echo isset($rs['class']) ? htmlentities($rs['class']) : ''; ?>">
				</p>
			</div>
		</div>
		<?php if(isset($rs['menu_id'])) { ?>
			<input type="hidden" name="menu_id" value="<?php echo intval($rs['menu_id']); ?>">
		<?php } ?>
		<div class="form-controls">
			<button type="submit" class="positive"><img src="/update/_elements/images/icons/tick.png" alt=""> Save</button>
		</div>
		<div class="clear"></div>
	</form>

	<?php
} else {
	echo $acl->accessDeniedMsg();
}


