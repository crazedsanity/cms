<?php

use crazedsanity\core\ToolBox;


// Save Media Sort
if(isset($clean["sort"])) {
	$ids = explode(",", $clean["sort"]);
	$num = 1;
	foreach ($ids as $node) {
		unset($sort);
		if($node > 0) {
			$sort['sort'] = $num;
			$db->update("pages", $sort, "page_id=" . $node);
			$num++;
		}
	}
	addMsg("Success", "Page sorting updated (". $num .").");
	ToolBox::conditional_header('/update/pages');
	exit;
}

if(!isset($category))
	$category = '';

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
$sql = "SELECT * FROM pages WHERE parent_id = '" . $parent_id . "' ORDER BY sort ASC";

$pages = $db->fetch_array($sql);

$_TEMPLATE['PAGE_TITLE'] = 'Sort ' . ucfirst($category) . ' Pages';

?>

<p><a href="/update/pages/index.php" id="return">View All</a></p>
<ul id="sortable">
	<?php foreach ($pages as $page) { ?>
		<li rel="<?php echo intval($page['page_id']); ?>" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><?php echo htmlentities($page['title']); ?></li>
	<?php }; ?>
</ul>
<form method="POST" action="?parent_id=<?php echo intval($parent_id); ?>" enctype="multipart/form-data">
	<input type="hidden" name="sort" id="sort" value="">
	<button type="submit" class="positive"><img src="/update/_elements/images/icons/tick.png" alt=""> Save</button>
</form>

