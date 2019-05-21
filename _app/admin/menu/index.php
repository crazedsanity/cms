<?php
$_TEMPLATE['PAGE_TITLE'] = 'Menu';

function getMenus($array = array(), $menu_id = 0, $parent_id = 0, $indent = '', $query = TRUE) {
	global $db;

	if($query == TRUE) {
		$sql = <<<SQL
				SELECT 
					#menu_items
					mi.menu_item_id,
					mi.title,
					mi.parent_id,
					mi.sub_menu_id,
					mi.link,
					#menus
					m.name,
					m.menu_id
				FROM menu_items AS mi
				RIGHT JOIN menus AS m ON m.menu_id = mi.menu_id
				ORDER BY m.menu_id, mi.sort
SQL;
		$items = $db->fetch_array($sql);
	} else {
		$items = $array;
	}
	$output = '';
	foreach ($items as $item) {
		if($menu_id != $item['menu_id'] && $query) {
			// new menu
			if($output != '') {
				// this is not the first new menu
				$output .= '</tbody></table>';
			}
			$output .= <<<HTML
					<table width="100%">
						<tr>
							<td><h3>{$item['name']}</h3></td>
							<td align="right">
								<div class="button-set">
									<a class="add" href="/update/menu/item.php?menu_id={$item['menu_id']}">Add</a>
									<a class="sort" href="/update/menu/sort.php?menu_id={$item['menu_id']}&parent_id={$item['parent_id']}">Sort</a>
									<a class="edit" href="/update/menu/menu.php?menu_id={$item['menu_id']}">Edit</a>
									<a class="delete formdelete" data-id="{$item['menu_id']}" data-keyname="menu_id" href="javascript:;"">Delete</a>
								</div>
							</td>
						</tr>
					</table>
					<table class="listingtable">
						<thead>
							<tr>
								<th>Title</th>
								<th>Link (if set)</th>
								<th>Options</th>
							</tr>
						</thead>
						<tbody>
HTML;
			$menu_id = $item['menu_id'];
		}
		// continue creating this menu if it is apart of the same menu and has the same parent and is not a null value
		if($menu_id == $item['menu_id'] && $parent_id == $item['parent_id'] && $item['menu_item_id'] != NULL) {
			// Recursion:
			$child_output = getMenus($items, $item['menu_id'], $item['menu_item_id'], $indent . ' &nbsp &nbsp', FALSE);
			if(!empty($item['menu_item_id'])) {
				$output .= "<tr><td>";
				$subMenuName = '';
				if($item['sub_menu_id'] > 0) {
					foreach ($items as $key => $val) {
						if(isset($val['menu_id'])) {
							if($val['menu_id'] == $item['sub_menu_id'])
								$subMenuName = '(menu: ' . $val['name'] . ')';
						}
					}
					$item['title'] .= ' <span class="sub_menu">' . $subMenuName . '</span>';
				}
				$output .= $indent . $item['title'];

				if($child_output != '') {
					$output .= <<<HTML
							<a href="/update/menu/sort.php?menu_id={$item['menu_id']}&amp;parent_id={$item['menu_item_id']}">Sort Sub Items</a>
HTML;
				}
				$output .= <<<HTML
							</td>
							<td>{$item['link']}</td>
							<td>
								<a href="/update/menu/item.php?menu_item_id={$item['menu_item_id']}">Edit</a> | 
									<a data-id="{$item['menu_item_id']}" data-keyname="menu_item_id" class="formdelete" href="javascript:;">Delete</a>
							</td>
						</tr>
HTML;
			} else {
				$output .= '<tr><td colspan="2">&nbsp;</td></tr>';
			}
			$output .= $child_output;
		}
	}

	return $output;
}

?>

<script>
	$(document).ready(function () {
		$('#add, .add').button({icons: {primary: "ui-icon-plus"}});
		$('.sort').button({icons: {primary: "ui-icon-carat-2-n-s"}});
		$('.edit').button({icons: {primary: "ui-icon-pencil"}});
		$('.delete').button({icons: {primary: "ui-icon-close"}});
		$('.button-set').buttonset();
	});
</script>


<?php if($acl->access($_SESSION['MM_Username'], 'menu', 0, ADD)) { ?>
	<p><a href="/update/menu/menu.php" id="add">Add New Menu</a></p>
<?php } ?>

<?php echo getMenus(); ?>

</table>

