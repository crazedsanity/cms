<?php
$user->superadmin = 1;
$user->restrict();

// Save Media Sort
if(isset($clean["sort"])) {
	$ids = explode(",", $clean["sort"]);
	$num = 1;

	foreach ($ids as $node) {
		unset($sort);
		if($node > 0) {
			$sort['sort'] = $num;
			$sort['menu_id'] = $clean['menu_id'];
			$db->update("menu_items", $sort, "menu_item_id=" . intval($node));
			$num++;
		}
	}
	$alert = "Sort updated.";
	addMsg("Sort Updated", "Result of sort operation: ". $num);
	crazedsanity\core\ToolBox::conditional_header('/update/menu/');
	exit;
}

if(empty($clean['menu_id'])) {
	crazedsanity\core\ToolBox::conditional_header('/update/menu/');
	exit;
}
if(empty($mysql['menu_id'])) {
	$mysql['menu_id'] = 0;
} else {
	$mysql['menu_id'] = intval($mysql['menu_id']);
}
if(empty($mysql['parent_id'])) {
	$mysql['parent_id'] = 0;
} else {
	$mysql['parent_id'] = intval($mysql['parent_id']);
}

$sql = "SELECT * FROM menu_items WHERE menu_id ='{$mysql['menu_id']}' AND parent_id='{$mysql['parent_id']}' ORDER BY sort ASC";

$pages = $db->fetch_array($sql);
?>
<script>
	$(document).ready(function () {
		$('#return').button({icons: {primary: "ui-icon-arrowreturnthick-1-w"}});
	});
</script>

<h2 class="page-title">Sort Menu</h2>
<p><a href="/update/menu/index.php" id="return">View All</a></p>
<ul id="sortable">
	<?php foreach ($pages as $page) { ?>
		<li rel="<?php echo intval($page['menu_item_id']); ?>" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><?php echo htmlentities($page['title']); ?></li>
	<?php } ?>
</ul>
<form method="POST" action="" enctype="multipart/form-data">
	<input type="hidden" name="sort" id="sort" value="">
	<input type="hidden" name="menu_id" id="menu_id" value="<?php echo intval($clean['menu_id']); ?>">
	<button type="submit" class="positive"><img src="/update/_elements/images/icons/tick.png" alt=""> Save</button>
</form>
