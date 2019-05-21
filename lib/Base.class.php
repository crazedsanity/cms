<?php

class Base {

	public $media_dir = '';
	public $clean = '';
	public $mysql = '';
	public $imgtypes = array();

	function isOgImage($filename) {

		$og_width = 1200;
		$og_height = 630;


		if($filename && file_exists(MEDIA_DIR . '/' . $filename) && filesize(MEDIA_DIR . '/' . $filename) > 0) {
			$image = getimagesize(MEDIA_DIR . '/' . $filename);

			if($image[0] >= $og_width && $image[0] >= $og_height) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function image_types() {
		global $db;
		if(!$this->imgtypes) {
			$sql = "SELECT ext FROM mimes WHERE image = 1";
			$images = $db->fetch_array($sql);

			foreach ($images as $image) {
				$this->imgtypes[] = strtolower($image['ext']);
			}
		}
		return $this->imgtypes;
	}

	function is_image($filename) {
		$pieces = explode('.', $filename);
		$pieces = array_reverse($pieces);
		$ext = '.' . $pieces[0];

		if(!$this->imgtypes) {
			$this->imgtypes = $this->image_types();
		}

		if(in_array(strtolower($ext), $this->imgtypes)) {
			return true;
		} else {
			return false;
		}
	}

	function is_pdf($filename) {
		$pieces = explode('.', $filename);
		$pieces = array_reverse($pieces);
		$ext = $pieces[0];
		if($ext == 'pdf')
			return true;
		else
			return false;
	}

	/**
	 * Gets selected menu and returns it as an html unordered list string.
	 * @param int $menu_id - Set to the id in the menus table for the menu you wish to call.
	 * @param boolean $sub_menus - Set to FALSE if you do not want other menus appended to this menu when applicable. This is an advanced menu feature that may not be used on all sites.
	 * @param int $max_levels - Set equal to the maximum amount of levels in the unordered list you desire. -1 is unlimited.
	 * @param int $current_id - Set to the current page_id if you wish to have the class "current" applied to the li tag.
	 * @param int $parent_id - Leave blank when calling menu unless you want to use a menu but skip a certain level of parents. (i.e. setting to 1 would skip first level of parents)
	 * @param array $menu_array - Leave blank. Used for passing data when function calls itself.
	 * @param int $sql_loop_guard - If you have more sub menus than the default value, you may want to increase this number. This is used to prevent an infinite loop if a sub menu were to have the parent menu as a sub menu inside it.
	 * @return string
	 * 
	 * TODO: Trim this down and consolidate params
	 */
	function getMenu($menu_id, $sub_menus = TRUE, $max_levels = -1, $current_id = 0, $parent_id = 0, $menu_array = array(), $sql_loop_guard = 20, $menuitemparents = array()) {
		global $db;

		if($max_levels != false) {
			if($max_levels > 0) {
				$max_levels--;
			}

			$return = '';
			$parent_id = intval($parent_id);
			$idand = '';

			if(FALSE != $menu_id && empty($menu_array)) {
				$sql = <<<SQL
						SELECT 
							# main menu
							m.name,
							m.class as menu_class,
							#menu_items
							mi.menu_item_id, 
							mi.page_id,
							mi.link,
							mi.title,
							mi.sort,
							mi.parent_id,
							mi.sub_menu_id,
							mi.class,
							#page_items
							p.title AS page_title,
							p.url AS url
						FROM menu_items mi
						LEFT JOIN pages p ON p.page_id = mi.page_id
						INNER JOIN menus AS m ON (mi.menu_id=m.menu_id)
						WHERE mi.menu_id = {$menu_id}
						ORDER BY mi.sort ASC, mi.title ASC
SQL;
				$items = $db->fetch_array($sql);

				$menuitemparents = $this->getMenuItemParents($current_id);
			} else {
				$items = $menu_array;
				$menuitemparents = $menuitemparents;
			}
			if(count($items) > 0 && is_array($items)) {
				$counter = 0;
				foreach ($items as $y => $item) {
					if($parent_id == $item['parent_id']) {
						if(0 == $counter) {
							$return = '<ul class="';
//							.  .'">';
							if(!empty($item['menu_class'])) {
								$return .= ' '. $item['menu_class'];
							}
							$return .= '">';
							$counter++;
						}

						$class = $item['class'];
						if($item['page_id'] == $current_id) {
							// if $item['link'] is used and menu is generated for home page, anything with $item['link'] would show true. So we do the following to prevent that.
							if($item['link'] == '')
								$class .= ' current active ';
						} else if(in_array($item['menu_item_id'], $menuitemparents) === true) {
							$class .= ' parent ';
						}
//						$class .= ' loop'. $counter .' ';

						if(str_word_count($item['title']) > 3) {
							//$class .= ' two ';
						}

						if($y == 0)
							$class .= ' first ';
						if($y + 1 == count($items))
							$class .= ' last ';

						$target = '';
						$error = false;
						if($item['link']) {

							if($item['link'] == '/') {
								$link = $item['link'];
							} else if(strpos($item['link'], '/') === 0) {
								$link = $item['link'];
							} else {
								if(strpos($item['link'], 'http://') === false && strpos($item['link'], 'https://') === false) {
									$item['link'] = 'http://' . $item['link'];
								}



								$link = $item['link'];
								$target = ' target="_blank" ';
							}
						} else {
							if(!$item['title']) {
								if(!$item['page_title'])
									$error = true;
								else
									$item['title'] = $item['page_title'];
							}
							if(!$item['url'])
								$item['url'] = '/';
							$link = $item['url'];
						}
						
//						$class .= "menu_item_id_". $item['menu_item_id'] ." parent_id_". $item['parent_id'] ." max_levels_". $max_levels ." internal-loops-". $this->loops;

						if(!$error) {
							$return .= <<<HTML
									<li class="{$class}">
										<a href="{$link}" {$target}><div>{$item['title']}</div></a>
HTML;
							if($sub_menus && $item['sub_menu_id'] > 0 && $sql_loop_guard > 0) {
								$sql_loop_guard--;
								$subMenuId = $item['sub_menu_id'];
								$menuItemId = 0;
								$passItems = array();
							} else {
								$subMenuId = null;
								$menuItemId = $item['menu_item_id'];
								$passItems = $items;
							}
							$return .= $this->getMenu($subMenuId, $sub_menus, $max_levels, $current_id, $menuItemId, $passItems, $sql_loop_guard, $menuitemparents);
							
							$return .= "</li>";
						}
					}
				}
				if($counter > 0)
					$return .= '</ul>';
			}

			if('' == $return) {
				$sql = "SELECT parent_id FROM menu_items WHERE menu_item_id='{$parent_id}'";
				$parent = $db->query_first($sql);

				if(isset($parent['parent_id']) && $parent['parent_id']) {
					$return = $this->getMenu($menu_id, false, 1, $current_id, $parent['parent_id']);
				}
			}

			return $return;
		}
	}

	function getMenuItemParents($page_id, $menu_id = '') {
		global $db;
		$pageId = intval($page_id);

		$where = "";
		if($menu_id) {
			$where = ' AND mi.menu_id = ' . $menu_id;
		}
		$sql = <<<SQL
				SELECT
					mi.menu_item_id AS 1id, mi.page_id as 1pid,
					mi2.menu_item_id AS 2id, mi2.page_id as 2pid,
					mi3.menu_item_id AS 3id, mi3.page_id as 3pid,
					mi4.menu_item_id AS 4id, mi4.page_id as 4pid,
					mi5.menu_item_id AS 5id, mi5.page_id as 5pid
				FROM menu_items mi
				LEFT JOIN menu_items mi2 ON mi2.menu_item_id = mi.parent_id
				LEFT JOIN menu_items mi3 ON mi3.menu_item_id = mi2.parent_id
				LEFT JOIN menu_items mi4 ON mi4.menu_item_id = mi3.parent_id
				LEFT JOIN menu_items mi5 ON mi5.menu_item_id = mi4.parent_id
				WHERE mi.page_id = '{$pageId}' {$where}
SQL;

				
		$parents = $db->fetch_array($sql);
		$return = array();
		if(is_array($parents) && isset($parents[0])) {
			$parents = $parents[0];


			if($parents['1id']) {
				$return[] = $parents['1id'];

				if($parents['2id']) {
					$return[] = $parents['2id'];

					if($parents['3id']) {
						$return[] = $parents['3id'];

						if($parents['4id']) {
							$return[] = $parents['4id'];

							if($parents['5id']) {
								$return[] = $parents['5id'];
							}
						}
					}
				}
			}
		}
		return $return;
	}

	function getCleanLink($page_id) {
		global $db;
		$sql = "SELECT page_id, parent_id, url FROM pages WHERE page_id = '$page_id'";
		$page = $db->query_first($sql);
		$url = $page['url'];

		return $url;
	}

	static function makeOptionsList($list, $selected = null) {
		$return = '';
		foreach ($list as $item_value => $textvalue) {
			$selectedText = $selected == $item_value ? ' selected="selected"' : '';
			$return .= "<option value='{$item_value}'{$selectedText}>{$textvalue}</option>";
		}
		return $return;
	}

	static function makeOptionsListFromArray(array $data, $valueIndex, $displayIndex, $selectThis = null) {
		$return = '';

		foreach ($data as $k => $v) {
			$select = '';
			if($selectThis == $v[$displayIndex] || $selectThis == $v[$valueIndex]) {
				$select = " selected='selected'";
			}
			$return .= "<option value='{$v[$valueIndex]}'{$select}>{$v[$displayIndex]}</option>\n";
		}

		return $return;
	}

	function makeCheckbox($name, $value, $selected = '') {
		$checkedValue = ( is_string($selected) && $selected == $value ) ? ' checked="checked"' : '';
		return "<input type='checkbox' name='{$name}' value='{$value}'{$checkedValue}>";
	}

	function makeCheckboxList($name, $list, $selected) {
		$return = '';
		foreach ($list as $val => $text) {
			$selectedText = ( ( is_array($selected) && in_array($val, $selected) ) || ( $selected == $val ) ) ? ' checked="checked"' : '';
			$return .= "<label class='checkbox_option'><input class='title' type='checkbox' name='{$name}[]' value='{$val}'{$selectedText}>{$text}</label>";
		}
		return $return;
	}

	function makeRadioList($name, $list, $selected) {
		$return = '';
		foreach ($list as $val => $text) {
			$selectedText = $selected == $val ? ' checked="checked"' : '';
			$return .= "<label class='radio_option'><input class='title' type='radio' name='{$name}' value='{$val}'{$selectedText}>{$text}</label>";
		}
		return $return;
	}

	function getStates() {
		global $db;

		if(!$_SESSION['states']) {
			$sql = <<<SQL
					SELECT state, abbreviation
					FROM states
					WHERE country = 'US'
					ORDER BY abbreviation
SQL;
			$sqlstates = $db->fetch_array($sql);
			$states = array();
			foreach ($sqlstates as $state) {
				$states[$state['state']] = $state['abbreviation'];
			}
			$_SESSION['states'] = $states;
		}

		return $_SESSION['states'];
	}

	function getProvinces() {
		global $db;

		if(!$_SESSION['provinces']) {
			$sql = <<<SQL
					SELECT state, abbreviation
					FROM states
					WHERE country = 'CANADA'
					ORDER BY abbreviation
SQL;
			$sqlstates = $db->fetch_array($sql);
			$states = array();
			foreach ($sqlstates as $state) {
				$states[$state['state']] = $state['abbreviation'];
			}
			$_SESSION['provinces'] = $states;
		}

		return $_SESSION['provinces'];
	}

	
	/**
	 * 
	 * @global Database $db
	 * @param str $name
	 * @param int $folder_id
	 * @param int $current_media_id
	 * @param int $deleteable
	 * @return object ("stdClass")
	 */
	function insertMediaAsset($name, $folder_id = 0, $current_media_id = 0, $deleteable = 1) {
		global $db;



		$media = new stdClass();
		if($this->clean['remove' . $name] == '1') {
			$sql = "SELECT filename FROM media WHERE media_id = '{$current_media_id}'";
			$current = $db->query_first($sql);
			if(file_exists($this->media_dir . $current['filename']) && $current['filename']) {
				unlink($this->media_dir . $current['filename']);
			}
			$media->error = false;
			$media->media_id = 0;
		}

		if($_FILES[$name]['name']) {

			$media = $this->insertMedia($name, $current_media_id, $folder_id, 0, $deleteable);
		} else {
			$media->error = false;
			$media->media_id = $current_media_id;
		}
		return $media;
	}

	function insertMedia($key, $current_id = 0, $mediaFolderId = 1, $is_folder = 0, $deleteable = 1) {
		global $db;

		$return = new stdClass();
		$return->error = false;

		if($_FILES[$key]['name']) {

			if(!$is_folder)
				$is_folder = 0;

			/*
			  $key = key within $_FILES array
			  $allowed_filetypes defined in core.php
			  $allowed_imagetypes defined in core.php
			  MEDIA_DIR defined in core.php
			 */
			if($current_id && $current_id > 0) {
				$sql = "SELECT filename FROM media WHERE media_id = '$current_id'";
				$current_filename = $db->query_first($sql);
				$current_filename = $current_filename['filename'];
			}

			$media['filename'] = $this->getNonDuplicateFileName(MEDIA_DIR, $_FILES[$key]["name"]);

			if(!$_FILES[$key]['error'] && $_FILES[$key] && $media['filename']) {
				$tmp_name = $_FILES[$key]["tmp_name"];

				if($this->checkAllowedFileType($_FILES[$key]["type"], array(), $media['filename'])) {
					if((!file_exists(MEDIA_DIR . $media['filename']) || $media['filename'] == $current_filename ) && $media['filename']) {

						if(file_exists(MEDIA_DIR . $current_filename) && $current_filename) {
							unlink(MEDIA_DIR . $current_filename);
							$update = true;
						}
						move_uploaded_file($tmp_name, MEDIA_DIR . $media['filename']);

						$media['filesize'] = filesize(MEDIA_DIR . $media['filename']);
						$media['filetype'] = mime_content_type(MEDIA_DIR . $media['filename']);
						$media['is_folder'] = $is_folder;
						$media['media_folder_id'] = $mediaFolderId;
						$media['deleteable'] = $deleteable;

						try {
							if($update) {
								$return->type = "update";
								$db->update("media", $media, "media_id=" . $current_id);
							} else {
								$return->type = "insert";
								$current_id = $db->insert("media", $media);
							}

							if(mime_content_type(MEDIA_DIR . $media['filename']) == 'application/pdf') {
								//make thumbnails for all pdfs
								if(!file_exists(MEDIA_DIR . 'pdfthumb_' . $media['filename'] . '.jpg'))
									exec('convert "' . MEDIA_DIR . $media['filename'] . '[0]" -colorspace RGB -geometry 300 "' . MEDIA_DIR . 'pdfthumb_' . $media['filename'] . '.jpg"');
							}
							
							
							$return->media_id = $current_id;
							$return->media = $media;
						}
						catch(PDOException $ex) {
							$return->error = true;
							$return->errormsg = $ex->getMessage();
						}
						catch(Exception $ex) {
							$return->error = true;
							$return->errormsg = $ex->getMessage();
						}
					} else {
						$return->error = true;
						$return->errormsg = 'An file with that name already exists.  Please rename your file.<br>';
					}
				} else {
					$return->error = true;
					$return->errorfiletype = $_FILES[$key]["type"];
					$return->errormsg = 'The file you uploaded does not appear to be an allowed file type.<br>';
				}
			} else if($_FILES[$key]['error'] && $_FILES[$key] && $_POST[$key]) {
				$return->error = true;
				$return->errormsg = 'The file you are uploading contains an error.';
			} else {

				if($_POST['filename'])
					$media['filename'] = mysql_escape_string($_POST['filename']);
				else
					unset($media['filename']);

				if(!$mediaFolderId)
					$mediaFolderId = 0;

				$media['is_folder'] = $is_folder;
				$media['parent_id'] = $mediaFolderId;
				$media['media_folder_id'] = $mediaFolderId;
				$media['deleteable'] = $deleteable;

				if($current_id && $current_id > 0)
					$db->update("media", $media, "media_id=" . $current_id);
				else
					$current_id = $db->insert("media", $media);

				$return->media_id = $current_id;
				$return->media = $media;
			}

			return $return;
		} else if($is_folder) {

			$media['filename'] = $_POST[$key];
			$media['is_folder'] = $is_folder;
			$media['deleteable'] = $deleteable;

			if($current_id)
				$db->update("media", $media, "media_id=" . $current_id);
			else
				$current_id = $db->insert("media", $media);
		}
	}

	function checkAllowedFileType($value, $limit = array(), $filename = '') {
		global $db;
		$limitsql = '';
		if(!$limit)
			$limit = array();

		if(count($limit) > 0) {
			$limitlist = implode('","', $limit);
			$limitsql = 'AND ext IN ("' . $limitlist . '")';
		}

		if($this->is_pdf($filename) && $filename)
			$value = 'application/pdf';

		if(strpos($value, '/') > 0) {
			//we are checking based on a mime type
			$sql = "SELECT mime, ext FROM mimes WHERE mime = '{$value}' AND allowed = 1 {$limitsql}";
		} else {
			//we are checking based on an extension
			$sql = "SELECT mime, ext FROM mimes WHERE ext = '{$value}' AND allowed = 1 {$limitsql}";
		}

		$count = $db->fetch_array($sql);
		
		if(count($count) > 0)
			return true;
		else
			return false;
	}

	function getNonDuplicateFileName($pathtofile, $filename) {
		if(file_exists($pathtofile . $filename)) {
			$filearray = explode('.', $filename);
			$fileext = end($filearray);
			array_pop($filearray); // remove last array element aka the file extension.

			$filename = implode('.', $filearray);

			$counter = 1;
			while ($counter <= 1000000) {
				$counter++;
				$testfilename = $filename . '_' . $counter . '.' . $fileext;

				if(!file_exists($pathtofile . $testfilename)) {
					return $testfilename;
				}
			}
			return 'error';
		} else {
			return $filename;
		}
	}

	public static function cleanString($string) {
		$string = trim($string);
		$string = str_replace(' ', '-', $string);
		$string = strtolower($string);
		$string = preg_replace('#[^a-z0-9\s-_]#i', '', $string);

		return $string;
	}

	function makePDF($src, $dest) {

		$dest = explode('.', $dest);
		$ext = $dest[1];
		$dest = $this->cleanString($dest[0]);
		$dest .= '.' . $ext;

		$dest = $this->getNonDuplicateFileName(MEDIA_DIR, $dest);

		exec('wkhtmltopdf --no-outline ' . $src . ' ' . MEDIA_DIR . '/' . $dest);

		return array('path' => '/data/upfiles/media/' . $dest, 'name' => $dest);
	}

	function adminMediaInput($filename, $label, $name = 'image', $caption = '') {
		if($caption) {
			$caption = ' - <i>' . $caption . '</i>';
		}
		$return = <<<HTML
				<p><label for="input_{$name}">{$label}</label> {$caption}<br>
				<input type="file" class="title" id="input_{$name}" name="{$name}">
HTML;

		if($filename && file_exists(MEDIA_DIR . $filename)) {

			$return .= <<<HTML
					<b>Path to file:</b> /data/upfiles/media/{$filename}<br>
					<label><input type="checkbox" value="1" name="remove{$name}"> Remove Current File</label><br><br>
HTML;

			$ftype = mime_content_type(MEDIA_DIR . $filename);
			$fileUrl = urlencode($filename);

			if($this->is_image($filename)) {
				$return .= "<img src='/_elements/thumb.php?x=150&amp;y=150&amp;i={$fileUrl}'>";
			} else if($this->is_pdf($filename)) {
				$return .= "<a href='/data/upfiles/media/{$fileUrl}'><img src='/_elements/thumb.php?x=150&amp;y=150&amp;i={$fileUrl}'></a>";
			} else {
				$return .= "<a href='/data/upfiles/media/{$fileUrl}'>{$filename}</a>";
			}
		}

		$return .= '<br/><br/><br /><br/></p>';
		return $return;
	}

	function getThisPageMenuItemId($menu_id, $page_id) {
		global $db;
		$sql = <<<SQL
				SELECT menu_item_id
				FROM menu_items mi
				WHERE mi.page_id = {$page_id}
					AND mi.menu_id = {$menu_id}
SQL;
		$menuitem = $db->query_first($sql);
		return $menuitem['menu_item_id'];
	}

	function getBreadcrumbs($page_id, $parent_id, $loop = 0, $pages = '') {
		global $db;

		$return = array();
		$loop++;

		if($loop <= 20) { //infinite loop protection
			if(!$pages) { // get call pages once to avoid multiple queries
				$sql = "SELECT page_id, title, parent_id FROM pages";
				$pages = $db->fetch_array($sql);
			}

			if(1 == $loop) { //find the current page on the first loop through
				foreach ($pages as $i => $page) {
					if($page['page_id'] == $page_id) {
						$url = $this->getCleanLink($page['page_id']);
						$return[] = <<<HTML
								<li class="current"><a href="{$url}">{$page['title']}</a></li>
HTML;

						break;
					}
				}
			}

			foreach ($pages as $i => $page) { //loop through all pages
				if($page['page_id'] == $parent_id) { //find the parent page
					$url = $this->getCleanLink($page['page_id']);
					$return[] = <<<HTML
							<li><a href="{$url}">{$page['title']}&nbsp;/&nbsp;</a></li>
HTML;
					if($page['parent_id'] > 0) {
						// call function again to find the next highest page
						$return[] = $this->getBreadcrumbs($page['page_id'], $page['parent_id'], $loop, $pages);
					}
					break;
				}
			}
		}
		$return[] = <<<HTML
				<li><a href="/">Home&nbsp;/&nbsp;</a></li>
HTML;

		$return = array_reverse($return);  // reverse array so order is correct
		$return = implode('', $return);   // string elements together into a string
		$return = '<ol class="breadcrumb">' . $return . '</ol>';  // wrap in <ul> element

		return $return;
	}

	function getMenuOptions($indent = '', $menu_id = 0, $selected_id = 0, $parent_id = 0, $menu_item_id = 0, $page_array = array()) {
		global $db;
		
		if(!is_numeric($menu_item_id)) {
			$menu_item_id = 0;
		}
		if(!is_numeric($menu_id)) {
			$menu_id = 0;
		}

		$options = '';
		if($indent == '' && empty($page_array)) {
			$sql = <<<SQL
					SELECT `menu_item_id`, `title`, `parent_id`
					FROM `menu_items`
					WHERE `menu_id` = $menu_id
					AND `menu_item_id`!= $menu_item_id 
					ORDER BY `sort` ASC, `title` ASC
SQL;

			$pages = $db->fetch_array($sql);
		} else {
			$pages = $page_array;
		}

		foreach ($pages as $page) {
			if($page['parent_id'] == $parent_id && $page['menu_item_id'] != $menu_item_id) {
				$options .= '<option value="' . $page['menu_item_id'] . '"';
				if($selected_id == $page['menu_item_id']) {
					$options .= ' selected="selected" ';
				}
				$options .= '>' . $indent . ' ' . $page['title'] . '</option>';
				// Recursion
				$options .= $this->getMenuOptions($indent . '--', $menu_id, $selected_id, $page['menu_item_id'], $menu_item_id, $pages);
			}
		}

		return $options;
	}

	function getTopMenuOptions($selected_id, $current_menu) {
		global $db;
		
		if(!is_numeric($current_menu)) {
			$current_menu = 0;
		}

		$sql = <<<SQL
				SELECT `menu_id`, `name`
				FROM `menus`
				WHERE `menu_id`!=$current_menu
				ORDER BY `sort` ASC
SQL;
		$pages = $db->fetch_array($sql);
		foreach ($pages as $page) {
			$options .= '<option value="' . $page['menu_id'] . '"';
			if($selected_id == $page['menu_id']) {
				$options .= ' selected="selected" ';
			}
			$options .= '>' . $indent . ' ' . $page['name'] . '</option>';
		}
		return $options;
	}

	function setSiteMapXML($pages = array(), $id = 0, $priority = 0.9) {
		global $db;

		$http = 'http://' . $_SERVER['SERVER_NAME'];

		if(empty($pages) && 0 == $id) {
			$sql = "SELECT
						`page_id`,
						`parent_id`,
						DATE_FORMAT(`modified`, '%Y-%m-%d') AS `modified`,
						`url`
					FROM pages
					ORDER BY `sort`";
			$pages = $db->fetch_array($sql);
			$xmlOut = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			$xmlOut .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
			$xmlOut .= "\t<url>\n";
			$xmlOut .= "\t\t<loc>$http</loc>\n";
			$xmlOut .= "\t\t<priority>$priority</priority>\n";
			$xmlOut .= "\t</url>\n";
		} else {
			$xmlOut = '';
		}

		$priority = $priority - .1;
		foreach ($pages as $page) {
			if($page['parent_id'] == $id) {

				if($page['modified'] != '') {
					$modified = "\t\t<lastmod>{$page['modified']}</lastmod>\n";
				} else {
					$modified = '';
				}

				$xmlOut .= "\t<url>\n";
				$xmlOut .= "\t\t<loc>$http{$page['url']}</loc>\n$modified";
				$xmlOut .= "\t\t<priority>$priority</priority>\n";
				$xmlOut .= "\t</url>\n";
				// Recursive:
				$xmlOut .= $this->setSiteMapXML($pages, $page['page_id'], $priority);
			}
		}
		if($id == 0) {// only try to write this is the first call.
			$xmlOut .= "</urlset>";
			$fp = fopen(__DIR__ . '/../public_html/sitemap.xml', 'w');
			$writeRes = fwrite($fp, $xmlOut);
			fclose($fp);
		}
		return $xmlOut;
	}
	
	
	
	function geoCodeAddress($address){
		
		$return =array();
		
		$address = urlencode($address);
		
		// create a new cURL resource
		$ch = curl_init();
		
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=true");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// grab URL and pass it to the browser
		$json = curl_exec($ch);
		
		// close cURL resource, and free up system resources
		curl_close($ch);
		
		$data = json_decode($json);
		
		
		if($data->status=='OK'){
			
			$return['lat'] = $data->results[0]->geometry->location->lat;
			$return['lng'] = $data->results[0]->geometry->location->lng;
			$return['formatted_address'] = $data->results[0]->formatted_address;
		}

		return $return;
			
	}
	function reverseGeoCode($lat, $lng){ //will return an estimated street address
		
		$return = '';
		
		$address = urlencode($address);
		
		// create a new cURL resource
		$ch = curl_init();
		
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, "http://maps.googleapis.com/maps/api/geocode/json?latlng=".$lat.",".$lng."&sensor=true");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// grab URL and pass it to the browser
		$json = curl_exec($ch);
		
		// close cURL resource, and free up system resources
		curl_close($ch);
		
		$data = json_decode($json);
		
		if($data->status=='OK'){
			
			$return = $data->results[0]->formatted_address;
		}

		return $return;
			
	}

}
