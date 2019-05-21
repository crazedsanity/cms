<?php

use crazedsanity\core\ToolBox;
use crazedsanity\messaging\Message;
use crazedsanity\messaging\MessageQueue;

function makeStatOptionList($states, $selected) {
	$options = '';
	foreach ($states as $value => $name) {
		$selected = '';
		if ($value == $selected) {
			$selected = ' selected="selected" ';
		}
		$options .= '<option value="' . $value . '" ' . $selected . ' >' . $name . '</option>';
	}
	return $options;
}
function fd($date) {
	return date("l, F j", strtotime($date));
}
function getMedia($parent_id = 0, $db, $base) {
	$output = '';
	$sql = <<<EOSQL
		SELECT * FROM media WHERE parent_id = $parent_id;
EOSQL;
	$media = $db->fetch_array($sql);
	if (count($media) > 0 && $media) {
		foreach ($media as $row) {
			if ($row['is_folder']) {
				$output .= <<<OUTPUT
		<div class="accordionButton">{$row['filename']} <div class="controls">
			<a href="item.php?media_id={$row['media_id']}">Edit</a> 
OUTPUT;
				if ($row['deleteable']) {
					$output .= <<<OUTPUT
			| 
			<a href="delete.php?media_id={$row['media_id']}" onclick="return confirm('Are you sure?');">Delete</a>
OUTPUT;
				}
				$output .= <<<OUTPUT
			</div></div>
		<div class="accordionContent">
OUTPUT;
				$output .= getMedia($row['media_id'], $db, $base);
				$output .= '</div>';
			} else {
				$output .= <<<OUTPUT
		<div class="mediaRow">
		<div class="filename">
			<a href="item.php?media_id={$row['media_id']}">{$row['filename']}</a>
		</div>
		<div class="preview">
OUTPUT;
				if ($base->is_image(MEDIA_DIR . $row['filename'])) {
					$output .= <<<OUTPUT
			<img  
			src="/data/upfiles/media/{$row['filename']}" style="max-width: 25px; max-height: 25px;" />
			<div id="preview{$row['media_id']}">
				<img src="/data/upfiles/media/{$row['filename']}" style="max-width: 300px; max-height: 300px;" />
			</div>
OUTPUT;
				}
				$output .= <<<OUTPUT
		
		</div>
		<div class="controls">
			<a href="item.php?media_id={$row['media_id']}">Edit</a>
			
OUTPUT;
				if ($row['deleteable']) {
					$output .= <<<OUTPUT
			| 
			<a href="delete.php?media_id={$row['media_id']}" onclick="return confirm('Are you sure?');">Delete</a>
OUTPUT;
				}
				$output .= <<<OUTPUT
		</div></div>
OUTPUT;
			}
		}

	}
	return $output;
}

function getMedia2($db, $base) {
	$output = '';
	$sql = <<<EOSQL
		SELECT m.media_id, m.filename, m.filetype, m.filesize, m.deleteable, m2.media_id AS folder_id, m2.filename AS folder_name, m2.deleteable AS folder_deleteable
		FROM media m2
		LEFT JOIN media m ON m2.media_id=m.parent_id
		WHERE m2.is_folder = 1
		ORDER BY m2.filename, m.filename
EOSQL;
	$media = $db->fetch_array($sql);
	if (count($media) > 0 && $media) {
		$first = true;
		$current_folder_id = 0;
		foreach ($media as $row) {
			if ($row['folder_id'] != $current_folder_id) {
				if (!$first) {
					$output .= '</div>';
				}
				$first = false;

				$output .= <<<OUTPUT
					<h3>{$row['folder_name']}
OUTPUT;
				if ($row['folder_deleteable']) {
					$output .= <<<OUTPUT
						<a href="{PAGE_URL}/delete.php?media_id={$row['folder_id']}">Delete</a>
OUTPUT;

				}
				$output .= <<<OUTPUT
						<a href="{PAGE_URL}/item.php?media_id={$row['folder_id']}">Edit</a>
					</h3>
					<div>		
OUTPUT;

				$current_folder_id = $row['folder_id'];
			}
			if ($row['filename']) {
				if ($base->is_image($row['filename']) && file_exists(MEDIA_DIR . '/' . $row['filename'])) {
					$hover = "mediaPreview(" . $row['media_id'] . ", '" . $row['filename'] . "');";
					$preview = '<div class="preview"></div>';
				} else {
					$hover = '';
					$preview = '';
				}

				$output .= <<<OUTPUT
				<div class="row" id="row{$row['media_id']}" onmouseover="{$hover}">
					<span></span>
					{$row['filename']}
					{$preview}
					<div class="controls">
						<a href="{PAGE_URL}/item.php?media_id={$row['media_id']}">Edit</a>
OUTPUT;
				if ($row['deleteable']) {
					$output .= <<<OUTPUT
						<a href="{PAGE_URL}/delete.php?media_id={$row['media_id']}">Delete</a>
OUTPUT;
				}
				$output .= <<<OUTPUT
					</div>
				</div>
OUTPUT;
			}
		}
		$output .= '</div>';
	}
	

	return $output;
}

function getMediaFolders($parent_id = 0, $db) {
	$output = array();
	$sql = <<<EOSQL
		SELECT media_id, is_folder, filename WHERE parent_id = {$parent_id} ORDER BY filename
EOSQL;
	$folders = $db->fetch_array($sql);
	if (count($folders) > 0) {
		foreach ($folders as $folder) {

			$subfolders = getMediaFolders($folder['media_id'], $db);

			$output[$folder['media_id']] = $subfolders;
		}
		return $output;
	}

}
function getPageOptions($db, $page_id, $indent = '', $current_id = 0, $selected_id = 0, $where = TRUE, $query = FALSE) {
	$options = '';
	if ($query == FALSE) {
		if ($where == TRUE) {
			$where = <<<EOSQL
			WHERE page_id != $page_id
EOSQL;
		} else {
			$where = '';
		}
		$sql = <<<EOSQL
		SELECT page_id, parent_id, title
		FROM pages
		$where
		ORDER BY sort ASC, title
EOSQL;
		$pages = $db->fetch_array($sql);
	} else {
		$pages = $query;
	}
	if (is_array($pages)) {
		foreach ($pages as $page) {
			if ($page['parent_id'] == $current_id) {
				$options .= '<option value="' . $page['page_id'] . '"';
				if ($selected_id == $page['page_id']) {
					$options .= ' selected="selected" ';
				}
				$titleLength = strlen($page['title']);
				if ($titleLength > 30 && $titleLength) {
					$page['title'] = substr($page['title'], 0, 27) . '...';
				}
				$options .= '>' . $indent . ' ' . $page['title'] . '</option>' . "\n";
				$options .= getPageOptions($db, $page_id, str_replace('-', '&nbsp;', $indent) . ' -', $page['page_id'],
						$selected_id, FALSE, $pages);
			}
		}
	}
	return $options;
}

function getMenuOptions($db, $indent = '', $menu_id = 0, $selected_id = 0, $parent_id = 0, $menu_item_id = 0,
		$page_array = array()) {
	$options = '';
	if ($indent == '' && empty($page_array)) {
		$sql = <<<EOSQL
		SELECT `menu_item_id`, `title`, `parent_id`
		FROM `menu_items`
		WHERE `menu_id` = $menu_id
			AND `menu_item_id`!= $menu_item_id 
		ORDER BY `sort` ASC, `title` ASC
EOSQL;

		$pages = $db->fetch_array($sql);
	} else {
		$pages = $page_array;
	}
	foreach ($pages as $page) {
		if ($page['parent_id'] == $parent_id && $page['menu_item_id'] != $menu_item_id) {
			$options .= '<option value="' . $page['menu_item_id'] . '"';
			if ($selected_id == $page['menu_item_id']) {
				$options .= ' selected="selected" ';
			}
			$options .= '>' . $indent . ' ' . $page['title'] . '</option>';
			$options .= getMenuOptions($db, $indent . '--', $menu_id, $selected_id, $page['menu_item_id'], $menu_item_id,
					$pages);
		}
	}

	return $options;
}
function getTopMenuOptions($db, $selected_id, $current_menu) {
	$sql = <<<EOSQL
		SELECT `menu_id`, `name`
		FROM `menus`
		WHERE `menu_id`!=$current_menu
		ORDER BY `sort` ASC
EOSQL;
	$pages = $db->fetch_array($sql);
	foreach ($pages as $page) {
		$options .= '<option value="' . $page['menu_id'] . '"';
		if ($selected_id == $page['menu_id']) {
			$options .= ' selected="selected" ';
		}
		$options .= '>' . $indent . ' ' . $page['name'] . '</option>';
	}
	return $options;
}
function getPagesV1($db, $acl, $page_id = 0, $indent = '', $current_id = 0, $program_id = 0, $page_array = array(),
		$sort_method = 'title') {
	$html = '';
	if ($current_id == 0 && empty($page)) {
		$sql = <<<EOSQL
		SELECT page_id, parent_id, title
		FROM pages
		ORDER BY parent_id, sort ASC, title ASC
EOSQL;
		$pages = $db->fetch_array($sql);
	} else {
		$pages = $page_array;
	}
	foreach ($pages as $page) {

		if ($page['parent_id'] == $current_id) {
			$html_child = getPagesV1(FALSE, $acl, $page_id, $indent . '&nbsp;&nbsp;', $page['page_id'], $program_id, $pages);
			if ($html_child != '') {
				$sorthtml = '- <a href="/update/pages/sort.php?parent_id=' . $page['page_id'] . '">Sort Sub Pages</a>';
			} else {
				$sorthtml = '';
			}
			$html .= <<<HTML
			 <tr>
          <td><strong>{$indent} {$page['title']}</strong> {$sorthtml}</td>
          <td>
HTML;

			if ($acl->access($_SESSION['MM_Username'], 'pages', $page['page_id'], DELETE)) {

				$html .= <<<HTML
          		<a onclick="return confirm('Are you sure?')" href="delete.php?page_id={$page['page_id']}">Delete</a>
HTML;
			}
			$html .= ' | ';

			if ($acl->access($_SESSION['MM_Username'], 'pages', $page['page_id'], EDIT)) {
				$html .= <<<HTML
	          	<a href="item.php?page_id={$page['page_id']}">Edit</a>
HTML;

			}
			$html .= <<<HTML
			      </td>
        </tr>
HTML;
			$html .= $html_child;
		}
	}

	return $html;
}
function getPagesV2($db, $acl, $page = array(), $id = 0, $pageListCount = 0) {
	if (empty($page) && $id == 0) {
		$sql = <<<EOSQL
		SELECT `page_id`, `parent_id`, `title`, `url`
		FROM pages
		ORDER BY `sort`
EOSQL;
		$page = $db->fetch_array($sql);
	}
	$pageInfo = array('html' => '', 'count' => $pageListCount);
	$i = 0;
	$count = count($page);
	$subPages = 0;
	$subPageTotal = 0;
	while ($i < $count) {
		if ($page[$i]['parent_id'] == $id) {
			$subPageTotal++;
		}
		$i++;
	}
	$i = 0;
	while ($i < $count) {
		if ($page[$i]['parent_id'] == $id) {
			$page[$i]['marked'] = 1;#add a mark to this array item to let it be known that it was counted
			if ($subPages == 0) {
				if ($pageInfo['count'] != 0) {
					$class = ' id="pageList' . $pageInfo['count'] . '"';
				} else {
					$class = ' id="pageList"';
				}
				$pageInfo['html'] .= "<ul$class>";
				$pageInfo['count']++;
			}

			$subPages++;
			$pageInfo['html'] .= "<li id=\"item-{$page[$i]['parent_id']}-{$page[$i]['page_id']}\"><span class=\"left\"></span><span class=\"title\">"
					. $page[$i]['title'] . "</span><span class=\"right\"><span class=\"permissions\">";
			#if ($acl->access($_SESSION['MM_Username'], 'pages', 0, ADD)) {
			$pageInfo['html'] .= '<a href="item.php?par=' . $page[$i]['page_id'] . '">Add Child</a> | ';
			#}
			#if ($acl->access($_SESSION['MM_Username'], 'pages', $page[$i]['page_id'], DELETE)) {
			$pageInfo['html'] .= "<a class=\"deletePage\" onclick=\"return confirm('Are you sure?')\" href=\"delete.php?page_id={$page[$i]['page_id']}\">Delete</a>";
			#}
			$pageInfo['html'] .= ' | ';
			#if ($acl->access($_SESSION['MM_Username'], 'pages',$page[$i]['page_id'], EDIT)) {
			$pageInfo['html'] .= "<a href=\"item.php?page_id={$page[$i]['page_id']}\">Edit</a>";
			$pageInfo['html'] .= ' | ';
			#}
			$pageInfo['html'] .= ' <a href="' . $page[$i]['url'] . '">View</a> ';
			$pageInfo['html'] .= ' <span class="handle" title="Reorder this page by moving it up or down."></span> ';
			$pageInfo['html'] .= '</span></span>';
			$temp = getPagesV2($db, $acl, $page, $page[$i]['page_id'], $pageInfo['count']);
			$pageInfo['html'] .= $temp['html'];
			$pageInfo['count'] = $temp['count'];
			$pageInfo['html'] .= "</li>";
		}
		$i++;
		if ($i == $count && $subPages > 0) {
			$pageInfo['html'] .= '</ul>';
		}
	}
	return $pageInfo;
}
function getMenusV0($db, $array = array(), $menu_id = 0, $parent_id = 0, $indent = '', $query = TRUE) {
	if ($query == TRUE) {
		$sql = <<<EOSQL
			SELECT 
				#menu_items
				mi.menu_item_id,
				mi.title,
				mi.parent_id,
				mi.sub_menu_id,
				#menus
				m.name,
				m.menu_id
			FROM menu_items AS mi
			RIGHT JOIN menus AS m ON m.menu_id = mi.menu_id
			ORDER BY m.menu_id, mi.sort
EOSQL;
		$items = $db->fetch_array($sql);
		#print_r($items);exit;
	} else {
		$items = $array;
	}
	$output = '';
	foreach ($items as $item) {
		if ($menu_id != $item['menu_id'] && $query == TRUE) {
			#new menu
			if ($output != '') {
				#this is not the first new menu
				$output .= '</tbody></table>';
			}
			$output .= <<<EOHTML
					<table width="100%"><tr><td width="50%"><h3>{$item['name']}</h3></td>
					<td align="right"><p><a href="item.php?menu_id={$item['menu_id']}">Add Menu Item</a> | <a href="sort.php?menu_id={$item['menu_id']}&parent_id={$item['parent_id']}">Sort Menu Items</a> |  <a href="menu.php?menu_id={$item['menu_id']}">Edit Menu</a> | <a href="delete.php?menu_id={$item['menu_id']}" onclick="return confirm('Are you sure?')">Delete Menu</a></p></td></tr></table>
					<table class="listingtable">
						<thead>
							<tr>
								<th>Title</th>
								<th>Options</th>
							</tr>
						</thead>
						<tbody>
EOHTML;
			$menu_id = $item['menu_id'];
		}
		#continue creating this menu if it is apart of the same menu and has the same parent and is not a null value
		if ($menu_id == $item['menu_id'] && $parent_id == $item['parent_id'] && $item['menu_item_id']!=NULL) {
			$child_output = getMenusV0($db, $items, $item['menu_id'], $item['menu_item_id'], $indent . ' &nbsp &nbsp', FALSE);
			if (!empty($item['menu_item_id'])) {
				$output .= <<<EOHTML
					<tr>
						<td>
EOHTML;
				$subMenuName='';
				if($item['sub_menu_id']>0){
					foreach($items as $key=>$val){
						if(isset($val['menu_id'])){
							if($val['menu_id']==$item['sub_menu_id']){
								$subMenuName='(menu: '.$val['name'].')';
							}
						}
					}
					$item['title'].= ' <span class="sub_menu">'.$subMenuName.'</span>';
				}
				$output.=$indent.$item['title'];

				if ($child_output != '') {
					$output .= <<<EOHTML
					<a href="sort.php?menu_id={$item['menu_id']}&parent_id={$item['menu_item_id']}">Sort Sub Items</a>
EOHTML;
				}
				$output .= <<<EOHTML
						</td>
						<td>
							<a href="item.php?menu_item_id={$item['menu_item_id']}">Edit</a> | <a href="delete.php?menu_item_id={$item['menu_item_id']}" onclick="return confirm('Are you sure?')">Delete</a>
						</td>
					</tr>
EOHTML;
			} else {
				$output .= '<tr><td colspan="2">&nbsp;</td></tr>';
			}
			$output .= $child_output;
		}
	}

	return $output;
}
function getMenusV1($db) {
	$sql = <<<EOSQL
			SELECT mi.menu_item_id, m.menu_id, mi.title, m.name
			FROM menu_items AS mi
			RIGHT JOIN menus AS m ON m.menu_id = mi.menu_id
			ORDER BY m.name, mi.sort
EOSQL;
	$items = $db->fetch_array($sql);
	if (count($items) > 0) {
		$first = true;
		$current_menu_id = 0;
		foreach ($items as $item) {
			if ($item['menu_id'] != $current_menu_id) {
				if (!$first) {
					$output .= <<<OUTPUT
	  						</tbody>
	  					</table>
OUTPUT;
				}
				$first = false;
				$output .= <<<OUTPUT
					<table width="100%"><tr><td width="50%"><h3>{$item['name']}</h3></td>
					<td align="right"><p><a href="item.php?menu_id={$item['menu_id']}">Add Menu Item</a> | <a href="sort.php?menu_id={$item['menu_id']}">Sort Menu Items</a> |  <a href="menu.php?menu_id={$item['menu_id']}">Edit Menu</a> | <a href="delete.php?menu_id={$item['menu_id']}" onclick="return confirm('Are you sure?')">Delete Menu</a></p></td></tr></table>
	  				<table class="listingtable">
	  					<thead>
							<tr>
								<th>Title</th>
								<th>Options</th>
							</tr>
	  					</thead>
	  					<tbody>
OUTPUT;
				$current_menu_id = $item['menu_id'];
			}
			if (!empty($item['menu_item_id'])) {
				$output .= <<<ECHO
					<tr>
						<td>
							<a href="item.php?menu_item_id={$item['menu_item_id']}">{$item['title']}</a>
						</td>
						<td>
							<a href="item.php?menu_item_id={$item['menu_item_id']}">Edit</a> | <a href="delete.php?menu_item_id={$item['menu_item_id']}" onclick="return confirm('Are you sure?')">Delete</a>
						</td>
					</tr>		
ECHO;
			} else {
				$output .= '<tr><td colspan="2">&nbsp;</td></tr>';
			}
		}
	}
	return $output;
}
function getCheckboxPages($selected, $name, $db, $page_id = 0, $indent = '', $current_id = 0) {
	$html = '';

	$sql = <<<EOSQL
		SELECT page_id, parent_id, title
		FROM pages
		WHERE parent_id = $current_id AND page_id != $page_id
		ORDER BY sort ASC, title ASC
EOSQL;

	$pages = $db->fetch_array($sql);
	if (count($pages) > 0) {
		foreach ($pages as $page) {
			if (count($selected) > 0 && in_array($page['page_id'], $selected)) {
				$checked = ' checked="checked" ';
			} else {
				$checked = '';
			}
			$html .= <<<HTML
			{$indent} <label><input type="checkbox" name="{$name}[]" value="{$page['page_id']}" {$checked} id="page_{$page['page_id']}"> {$page['title']}</label><br />
HTML;
			$html .= getCheckboxPages($selected, $name, $db, $page_id, $indent . '&nbsp;&nbsp;', $page['page_id']);
		}
	}

	return $html;
}
function hasChildren($page_id, $db) {
	if (intval($page_id) > 0) {
		$sql = <<<EOSQL
		SELECT * FROM pages WHERE parent_id={$page_id}
EOSQL;
		$children = $db->fetch_array($sql);
		if (count($children) > 1) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function getNonDuplicateDirectory($db, $url, $parent_id, $page_id = 0, $count = 1){
	$parent_url='';
	
	/*	@db object $db
	 * 	@url string $url
	 * 	@parent_id int $parent_id
	 * 	@page_id int $page_id
	 */
	#make URL consistent and reliable by removing non alpha-numerical characters except hypens and underscores
	$url = strtolower($url);
	#user hyphens by default to seperate words
	#Bing prefers hyphens - http://www.bing.com/community/webmaster/f/12256/p/658178/9592621.aspx#9592621
	#Google in the past preferred hyphens, may no longer make a difference - http://www.mattcutts.com/blog/whitehat-seo-tips-for-bloggers/
	$url = str_replace(' ', '-', trim($url));
	$url = str_replace('/', '', trim($url));
	$url = preg_replace("/[^\/A-Za-z0-9_-]/", "", $url);
	$url = '/'.$url.'/';
	
	
	
	if($parent_id>0){ //check and make sure parent exists
		$sql=<<<EOSQL
			SELECT page_id, url FROM pages WHERE page_id = {$parent_id}
EOSQL;
		$parent = $db->query_first($sql);
		$parent_id = $parent['page_id'];
		$parent_url = $parent['url'];		
	}
	else{
		$parent_id=0;
		$parent_url='/';	
	}
	
	
	$url = str_replace('//', '/', trim($parent_url.$url));
	
		
	
	$urlCheck = 0.5; #Set $urlCheck greater than zero but less than 1
	$safetyCounter = 0; #Prevent while loop from infinite loop
	$url_orig = $url;
	
	
	
	while ($urlCheck > 0 && $safetyCounter < 20) {
		if ($urlCheck >= 1) {
			#if url greater than one, remove forward slash at end of url, append a number, append forward slash back on
			$url = substr($url_orig, 0, -1);
			$url .= $safetyCounter . '/';
		}
		$sql = "SELECT count(`url`) as ct FROM `pages` WHERE `url` = '{$url}' AND `page_id` != " . $page_id ." AND parent_id = ".$parent_id;
		$pages = $db->query_first($sql);
		$urlCheck = $pages['ct'];
		$safetyCounter++;
	}
	
	
	return $url;
}
function setSiteMapXML($db, $pages = array(), $id=0, $priority = 0.9) {
	$http = 'http://'.$_SERVER['SERVER_NAME'];
	if (empty($pages) && $id == 0) {
		$sql = <<<EOSQL
		SELECT `page_id`,
			`parent_id`,
			DATE_FORMAT(`modified`, '%Y-%m-%d') AS `modified`,
			`url`
		FROM pages
		ORDER BY `sort`
EOSQL;
		$pages = $db->fetch_array($sql);
		$xmlOut = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>$http</loc>
		<priority>$priority</priority>
	</url>\n
EOXML;
		#Manually add pages here that do not have content in the pages table
	} else {
		$xmlOut = '';
	}
	$priority = $priority - .1;
	foreach ($pages as $page) {
		if ($page['parent_id'] == $id) {
			if($page['modified']!=''){
				$modified="\n\t\t<lastmod>{$page['modified']}</lastmod>";
			}else{
				$modified='';
			}
			$xmlOut .= <<<EOXML
	<url>
		<loc>$http{$page['url']}</loc>$modified
		<priority>$priority</priority>
	</url>\n
EOXML;
			$xmlOut .= setSiteMapXML($db, $pages, $page['page_id'], $priority);
		}
	}
	if ($id == 0) {
		$xmlOut .= "</urlset>";
	}
$fp=fopen('../../sitemap.xml','w');
fwrite($fp,$xmlOut);
fclose($fp);
return $xmlOut;
}


/**
 * Immediately prints the supplied data, with formatting to avoid having to 
 * view page source.
 * 
 * @param mixed	$data		Data to be dumped.
 * @param str	$title		Dump prefixed with this information
 * @param bool	$printIt	Option to print, overrides "DEBUGPRINT" declaration
 */
function debugPrint($data, $title=null, $printIt=null) {
	$result = false;
	if(is_null($printIt)) {
		$printIt = ToolBox::$debugPrintOpt;
	}
	if($printIt) {//don't bother doing any work if it's not going to be printed.
		$showTitle = "";
		if(!is_null($title) && strlen($title)) {
			$showTitle = $title .": ";
		}
		$printData = "";
		if(!is_null($data)) {
			$printData = print_r($data, true);
		}

		ToolBox::debug_print("<b>{$showTitle}</b>{$printData}", $printIt);
		$result = true;
	}
	return $result;
}

/**
 * Just a time-saver.  Returns a template object for the given asset file.
 * 
 * @param str $assetFile	Just use the __FILE__ constant.
 * @param str $tmplFile		Filename of the template to use/parse
 * @param str $name			Placeholder name; used when adding to a parent template.
 * 
 * @return \crazedsanity\template\Template
 */
function getAssetTemplate($assetFile, $tmplFile, $name=null) {
	$bits = explode('/assets/', $assetFile);
	
	$dir = TMPL_DIR .'/assets/'. $bits[1];
	if($tmplFile !== null) {
		$dir .= '/'. $tmplFile;
	}
	
	$tmpl = new \crazedsanity\template\Template($dir, $name);
	
	return $tmpl;
}

/**
 * Okay, this might be more laziness than short-hand... stop judging me.  It works.
 * 
 * @param str $tmplFile	Template filename.
 * @param type $name	Placeholder name; used when adding to a parent template
 * 
 * @return \crazedsanity\template\Template
 */
function getTemplate($tmplFile=null, $name=null) {
	global $_TEMPLATE;
	$path = null;
	if(!is_null($tmplFile)) {
		$path = TMPL_DIR .'/'. $tmplFile;
		
		// Try finding a ".html" file if the ".tmpl" file does not exist
		if(!file_exists($path) && preg_match('~\.tmpl$~', $path)) {
			$path = preg_replace('~tmpl$~', 'html', $path);
		}
	}
	$tmpl = new \crazedsanity\template\Template($path, $name);
	if(is_array($_TEMPLATE) && count($_TEMPLATE) > 0) {
		$tmpl->addVarList($_TEMPLATE);
	}
	return $tmpl;
}


function addAlert($title, $body, $type=null) {
	$x = new Message(false);
	$x->title = $title;
	
	if(empty($body) || strlen($body) < 2) {
		// no body provided? Put one in to avoid an uninformative exception
		$body .= "&nbsp;";
	}
	$x->body = $body;
	
	if(!empty($type) && in_array($type, Message::$validTypes)) {
		$x->type = $type;
	}
	else {
		$x->type = Message::DEFAULT_TYPE;
	}
	
	if($x->type == Message::TYPE_ERROR || $x->type == Message::TYPE_FATAL) {
		try {
			$module = $_SERVER['REQUEST_URI'];
			$logMessage = __METHOD__ .'::'. $x->type .' - TITLE: '. $title .' - BODY: '. $body;
			applicationLog($module, $logMessage);
		} catch (Exception $ex) {
			// nothing to see here, move along.
		}
	}
	$q = new MessageQueue(true);
	$q->add($x);
}

function readConfig() {
	
	$useIni = false;
	if(file_exists(__DIR__ . '/../config/siteconfig-dev.ini')) {
		$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig-dev.ini',true);
	}
	elseif(file_exists(__DIR__ . '/../config/siteconfig.ini')) {
		$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig.ini', true);
	}

	return $useIni;
}



/**
 * Generates a URL-safe string from a title ("About Stuff" -> "about-stuff")
 * 
 * @param string $pageTitle
 * @return string
 */
function generateSlug($pageTitle) {
	$pageTitle = str_replace("&", "-and-", $pageTitle);
	preg_match_all("~[\p{Ll}\p{Mn}\d]+~u", strtolower($pageTitle), $out);
	$final = preg_replace('~\-$~', '', preg_replace('~(\-{2,})~', '-', $out[0]));
	return implode('-', $final);
}

function applicationLog($module, $message, $stacktrace=null) {
	$db = $GLOBALS['db'];
	$logResult = null;
	
	if(is_object($module)) {
		$module = get_class($module);
	}
	
	try {
		$fields = array(
			'username'		=> '(Anonymous)',
			'admin_id'		=> 0,
			'module'		=> $module,
			'message'		=> $message,
			'ipaddress'		=> $_SERVER['REMOTE_ADDR'],
			'stacktrace'	=> $stacktrace,
		);
		
		if(isset($_SESSION['MM_Username'])) {
			$fields['username'] = $_SESSION['MM_Username'];
		}
		if(isset($_SESSION['MM_UserID'])) {
			$fields['admin_id'] = $_SESSION['MM_UserID'];
		}
		
		if(empty($stacktrace)) {
			$bt = new Exception();
			$fields['stacktrace'] = $bt->getTraceAsString();
		}
		debugPrint($fields, __METHOD__ ." - fields");
		
		$sql = "INSERT INTO log (username, admin_id, module, message, ipaddress, stacktrace) "
				. "VALUES (:username, :admin_id, :module, :message, :ipaddress, :stacktrace)";
		
		$logResult = $db->run_insert($sql, $fields);
	} catch (Exception $ex) {
		$logResult = $ex->getMessage();
	}
	
	return $logResult;
}
