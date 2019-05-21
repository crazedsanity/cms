<?php

namespace cms\cms\core;

use stdClass;

class settings extends core {
	
	private $_cache = null;

	function __construct($db) {
		$this->db = $db;
		parent::__construct($db, 'settings', 'setting_id');
		$this->base = new \Base();
	}

	public function getAssets() {
		$sql = "SELECT 
				sc.*, sc.setting_category_name AS clean_name 
			FROM 
				setting_categories AS sc
			ORDER BY setting_category_id";
		
		debugPrint($sql, __METHOD__ ." - sql");
		$this->db->run_query($sql);
		$data = $this->db->farray_fieldnames('setting_category_id');
		$this->_cache = $data;
		
		debugPrint($data, __METHOD__ ." - data");
		return $data;
	}

	function set($value, $name, $siteCategoryId = 0) {
		$sql = <<<SQL
			UPDATE settings
			SET value = '{$value}'
			WHERE name = '{$name}'
SQL;
		if(intval($siteCategoryId) > 0) {
			$sql .= " AND site_category_id={$siteCategoryId}";
		}
		$this->debugPrint($sql, "sql");
		
		try {
			$qid = $this->db->query($sql);
			$this->debugPrint($qid, "result of saving");

			$valid = true;
		}
		catch(Exception $ex) {
			$valid = $ex->getMessage();
		}

		return $valid;
	}

	function get($name, $asset) {
		$sql = <<<SQL
			SELECT s.*, sc.*, m.filename
			FROM settings s
				INNER JOIN setting_categories AS sc ON (s.setting_category_id=sc.setting_category_id)
			LEFT JOIN media m ON m.media_id = s.value
			WHERE s.name = '{$name}'
				AND sc.setting_code='{$asset}'
SQL;
				$this->debugPrint($sql, "sql");

		$settings = $this->db->fetch_array($sql);
		debugPrint($settings, "settings found");


		$setting = array(
			'value'	=> null,
		);
		if(count($settings) > 0) {
			$setting = $settings[0];

			if($setting['type'] == 'image') {
				$setting['value']=$setting['filename'];
			}
		}
		$return = new stdClass();
		$return->value = $setting['value'];
		
		$this->debugPrint($return, "returned data");

		return $return->value;
	}

	function input($type, $value, $name, $options = array()) {

		$return = '';
		switch (trim($type)) {
			case 'image': //any single line text value
				$return = $this->base->adminMediaInput($value, '', $name);
				$return.='<input type="hidden" name="settings[' . $name . ']" value="' . $value . '" />';
				break;
			case 'text': //any single line text value
				$return = '<input type="text" class="title" name="settings[' . $name . ']" value="' . $value . '">';
				break;
			case 'textarea': //any multiline text value
				$return = '<textarea class="title" name="settings[' . $name . ']">' . $value . '</textarea>';
				break;
			case 'wysiwyg': //any multiline text value
				$return = <<<EOF
					<textarea class="title" id="editor{$asset}{$asset_id}" name="settings[{$name}]">{$value}</textarea>
					<script type="text/javascript">CKEDITOR.replace( "editor{$asset}{$asset_id}" );
EOF;
				break;
			case 'select': // single select
				if(is_array($options) && count($options) > 0) {
					$return = <<<HTML
						<select class="title" name="settings[{$name}]">
							<option>Select One</option>
HTML;
					$return .= $this->base->makeOptionsList($options, $value);
					$return .= "</select>";
				}
				break;
			case 'radio': //multiple radio buttons
				if(is_array($options) && count($options) > 0) {
					$return = $this->base->makeRadioList('settings[' . $name . ']', $options, $value);
				}
				break;
			case 'checkbox': // multiple checkboxes
				if(is_array($options) && count($options) > 0) {
					$return = $this->base->makeCheckboxList('settings[' . $name . ']', $options, $value);
				}
				break;
		}
		return $return;
	}

	
	/**
	 * Retrieves all listings of a particular category (and displays HTML for them).
	 * 
	 * @param type $asset
	 * @param int $settingCategoryId
	 * @return type
	 * 
	 * TODO: stop having a class spit out HTML.
	 */
	function listing($settingCategoryId) {

		if(empty($settingCategoryId) || intval($settingCategoryId) == 0) {
//			$settingCategoryId = 0;
			throw new \InvalidArgumentException();
		}

		$sql = <<<SQL
			SELECT 
				s.*, sc.*, m.filename
			FROM
				settings s
				INNER JOIN setting_categories AS sc ON (s.setting_category_id = sc.setting_category_id)
				LEFT JOIN media m ON (m.media_id = s.value)
			WHERE s.setting_category_id = {$settingCategoryId}
			ORDER BY s.setting_id
SQL;
		debugPrint($sql, __METHOD__ ." - sql");

		$settings = $this->db->fetch_array($sql);
		debugPrint($settings, __METHOD__ ." - data");
		if(count($settings) > 0) {
			$return = "<table class='settings {$asset}'>";
			foreach ($settings as $setting) {
				$return .= <<<HTML
					<tr>
						<td class="name">
							<p><span class="title">{$setting['title']}</span>
HTML;
				if($setting['description']) {
					$return .= '<br><span class="desc">' . $setting['description'] . '</span>';
				}

				$options = '';

				if($setting['type'] == 'image') {
					$setting['value'] = $setting['filename'];
				}
				$input = $this->input($setting['type'], $setting['value'], $setting['name'], $options);

				$return .= <<<HTML
					</p>
					</td>
					<td class="input">
					{$input}
					</td>
					</tr>
HTML;
			}

			$return .= <<<HTML
				</table>
				<input type="hidden" name="setting_category_id" value="{$settingCategoryId}">
		
HTML;
				$this->debugPrint(htmlentities($return), "Returning html");
		}
		else {
			debugPrint($settings, __METHOD__ ." - no settings found");
			throw new \Exception("No settings found");
		}

		return $return;
	}

	function save($settings, $settingCategoryId = 0) {
		$return = new stdClass();
		$msg = '';
		$overallerror = false;


		foreach ($settings as $name => $value) {


			if(isset($_FILES[$name]) && $_FILES[$name]['name']) {
				debugPrint($_FILES[$name], __METHOD__ ." - FILES array");
				$_mObj = new media($this->db);
				$_fObj = new mediaFolder($this->db);
				$allFolders = $_fObj->getAll();
				if(count($allFolders) > 0) {
					$folderInfo = $allFolders[array_keys($allFolders)[0]];
					debugPrint($allFolders, __METHOD__ ." - all folder data");
					debugPrint($folderInfo, __METHOD__ ." - folder info");
//					exit;
				}
//				$media = $this->base->insertMediaAsset($name, 0, 0, 1);
				
				$insertData = array(
					'media_folder_id'	=> $folderInfo['media_folder_id'],
					'admin_id'			=> $_SESSION['MM_UserID'],
					'user'				=> $_SESSION['MM_Username'],
				);
				
				$recordId = $_mObj->upload($name, $folderInfo['path'], $insertData);
				$data = $_mObj->get($recordId);
				$value = $data['filename'];
				
				debugPrint($value, __METHOD__ ." - result of saving media");
//				exit;
//				$value = $media->media_id;
			}

			$status = $this->set($value, $name, $settingCategoryId);

			if($status !== true) {
				$overallerror = true;
				$msg .= $status . '<br>';
			}
		}

		if(!$overallerror) {
			$msg = 'Settings saved.';
		}
		$return->msg = $msg;
		$return->error = $overallerror;

		return $return;
	}

}
