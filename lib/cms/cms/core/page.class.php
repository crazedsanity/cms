<?php

namespace cms\cms\core;

class page extends core {
	protected $db;
	protected $data = array();
	
	public function __construct($db) {
		parent::__construct($db, 'pages', 'page_id');
	}
	
	
	/*
	 * Searches the pages for the given term.
	 * 
	 * TODO: ignore/handle pages that require authentication.
	 */
	public function searchPages($searchTerm) {
		$sql = "
			SELECT 
			DISTINCT(p.page_id), p.title, p.url, p.created, p.modified, date(p.created) as display_date
			FROM 
				pages p 
			WHERE 
				status='active'
				AND p.title LIKE '%". $searchTerm ."%'
				OR p.body LIKE '%". $searchTerm ."%'
			ORDER BY title";
		
		$result = $this->db->fetch_array($sql);
		
//		//break the tags into an array.
//		foreach($result as $i=>$data) {
//			$result[$i]['alltags'] = explode('|', $data['alltags']);
//		}
//		
//		if(is_array($filters) && count($filters)) {
//			$filterObject = new \cms\filter($this->db);
//			$result = $filterObject->filter($result, $filters);
//		}
		
		return $result;
	}
	
	
	public function getAll() {
		if(isset($this->data) && is_array($this->data) && count($this->data) > 0) {
			$data = $this->data;
		}
		else {
			$sql = "
				SELECT 
					p.*,
					g.name AS required_group_name,
					(
						select count(page_id) FROM pages p2 WHERE p2.parent_id=p.page_id
					) as _num_children
				FROM 
					pages as p
					LEFT OUTER JOIN groups AS g ON (p.required_group_id=g.group_id)
				ORDER BY 
					parent_id, 
					sort ASC, 
					title ASC";

			$data = $this->db->fetch_array_assoc($sql, 'page_id');

			foreach($data as $i=>$x) {
				$data[$i]['_depth'] = self::getPageDepth($i, $data);
			}

			$this->data = $data;
			
			// !!! IMPORTANT: this is only okay because of caching; no cache would create a MASSIVE performance hit.
			foreach($data as $k=>$v) {
				$data[$k]['_required_group_id'] = $this->getParentRequiredGroupId($v['page_id']);
			}
		}
		
		return $data;
	}
	
	
	public function get($id, $onlyActive=true) {
		$sqlOnlyActive = "";
		if($onlyActive) {
			$sqlOnlyActive = " AND p.status = 'active'";
		}
		$sql = "SELECT 
				p.*,
				g.name AS required_group_name,
				(
					select count(page_id) FROM pages p2 WHERE p2.parent_id=p.page_id
				) as _num_children,
				m.filename AS og_image_filename
			FROM 
				pages as p
				LEFT OUTER JOIN groups AS g ON (p.required_group_id=g.group_id)
				LEFT OUTER JOIN media AS m ON (p.og_image_media_id=m.media_id)
			WHERE
				p.page_id={$id}{$sqlOnlyActive}";
		$this->debugPrint($sql, "SQL");
		return $this->db->query_first($sql);
	}
	
	
	public static function getPageDepth($id, &$allData) {
		$depth = 0;
		$trace = "";
		while ($id != 0 ) {
			$oldId = $id;
			$id = self::getParent($id, $allData);
			if($depth == 0) {
				$trace = $oldId;
			}
			$trace .= ":". $id;
			$depth++;
			if($depth > count($allData)) {
				debugPrint("She's borken, captain");
				exit;
			}
		}
		
		
		return ($depth -1);
	}
	
	
	public function delete($id) {
		unset($this->data);
		$record = $this->get($id);
		$result = null;
		
		//ONLY delete if there are no children, we don't need orphans.
		if($record['_num_children'] == 0) {
			$sql = "DELETE FROM pages WHERE page_id={$id}";
			$this->db->query($sql);
			
			$result = $this->db->affected_rows;
		}
		
		return $result;
	}
	
	
	public function addTag($id, $tag) {
		\debugPrint(func_get_args(), __METHOD__ ." - arguments");
		$tagObj = new \cms\tag($this->db);
		$info = $this->get($id);
		return $tagObj->addTag($id, $info['url'], $info['title'], 'pages', $tag);
	}
	
	
	public function getByUrl($url, $onlyActive=true) {
		$sqlOnlyActive = "";
		if($onlyActive) {
			$sqlOnlyActive = " AND p.status = 'active'";
		}
		
		if(preg_match('~/$~', $url) == 1) {
			$firstUrl = $url;
			$secondUrl = preg_replace('~/$~', '', $url);
		}
		else {
			$firstUrl = $url .'/';
			$secondUrl = $url;
		}
		$urlSql = "(p.url='{$firstUrl}' OR p.url='{$secondUrl}')";
		$sql = "SELECT 
				p.*,
				(
					select count(page_id) FROM pages p2 WHERE p2.parent_id=p.page_id
				) as _num_children
			FROM 
				pages as p
			WHERE
				{$urlSql}{$sqlOnlyActive}";
				$this->debugPrint($sql, "SQL");
				
		$data = $this->db->query_first($sql);
		if(empty($data)) {
			$this->log(__CLASS__, "nothing found for ({$firstUrl}) or ({$secondUrl})");
		}
		
		return $data;
	}
	
	
	public function getByAsset($assetName) {
		$sql = "SELECT 
				p.*,
				(
					select count(page_id) FROM pages p2 WHERE p2.parent_id=p.page_id
				) as _num_children
			FROM 
				pages as p
			WHERE
				p.asset=:asset AND p.status = 'active'";
		$params = array(
			'asset'	=> $assetName,
		);
		$this->db->run_query($sql, $params);
		return $this->db->get_single_record();
	}
	
	
	
	public function getHomepage() {
		return $this->getByAsset('home');
	}
	
	
	/**
	 * Determines what the proper URL for the given ID should be... 
	 * 
	 * @param type $pageId
	 * 
	 * @return string
	 * @throws \LogicException
	 */
	private function getRealUrl($pageId) {
		if(!isset($this->_info[__FUNCTION__])) {
			$this->_info[__FUNCTION__] = 0;
		}
		$allParents = $this->getParents($pageId, $this->data);
$this->debugPrint($allParents, "all parents of (". $pageId .")");
		
		$realUrl = "";
		foreach(array_reverse($allParents) as $id) {
			if($id > 0) {
				$theData = $this->data[$id];
				$this->debugPrint($theData, "parent data for id=(". $id .")");

				$realUrl .= '/'. $theData['url'];
			}
$this->debugPrint($realUrl, "realURL (". __LINE__ .")");
		}
$this->debugPrint($realUrl, "dynamic URL (line #". __LINE__ .")");
		$retval = preg_replace('~\/{2,}~', '/',  $realUrl .'/');
$this->debugPrint($retval, "dynamic URL (line #". __LINE__ .")");
		
		return $retval;
	}
	
	
	public function create(array $data) {
		if(isset($data['clean_title'])) {
			unset($data['clean_title']);
		}
		$insertData = $data;
		
		$parentId = 0;
		$this->getAll();
		if(isset($data['parent_id']) && is_numeric($data['parent_id'])) {
			$parentId = $data['parent_id'];
		}
		$insertData['url'] = preg_replace('~//~', '/', $this->getRealUrl($parentId) .'/'. self::makeCleanTitle($insertData['title']) .'/');
		
		//TODO: check for existing pages with the same URL...
		
		$newId = $this->db->insert('pages', $insertData);
		return $newId;
	}
	
	
	public function update(array $data, $pageId) {
		unset($this->data);
		if(!is_numeric($pageId) || (is_numeric($pageId) && $pageId < 1)) {
			throw new \InvalidArgumentException("invalid pageId");
		}
		if(!is_array($data) || !count($data)) {
			throw new \InvalidArgumentException("no changes to perform");
		}
		
		if(isset($data['clean_title'])) {
			unset($data['clean_title']);
		}
		$updateRes = 0;
		$oldRecord = $this->get($pageId);
		
		$parentId = $oldRecord['parent_id'];
		if(isset($data['parent_id'])) {
			$parentId = $data['parent_id'];
		}
		$this->getAll();
		$this->debugPrint($this->getParents($pageId), "parents of this ID");
		$baseUrl = "";
		if(!isset($data['url']) || empty($data['url'])) {
			if(isset($parentId) && $parentId > 0) {
//				$baseUrl = $this->getRealUrl($pageId);
				$baseUrl = $this->data[$parentId]['url'];
			}
			$data['url'] = $baseUrl .'/'. self::makeCleanTitle($data['title']) .'/';
		}
		$data['url'] .= '/';
		$data['url'] = preg_replace('~/{2,}~', '/', $data['url']);
		$this->debugPrint($data['url'], "cleaned URL");
		
		
		$updateRes += $this->db->update('pages', $data, "page_id=" . $pageId);
		$this->debugPrint($updateRes, "result of updating page");
		$this->getAll();
		
		return $updateRes;
	}
	
	
	/**
	 * Retrieve's the given page's cleaned title, in case it was somehow different 
	 * than what we expect (if it was previously "cleaned" in a different way).
	 * If given ID=0, it returns an empty string.
	 * 
	 * @param type $id	Page ID to look up in cache.
	 * 
	 * @return string	The given page's cleaned title.
	 */
	public function getCleanTitle($id) {
		$retval = "";
		if($id > 0) {
			$data = $this->data[$id];
			$bits = explode('/', preg_replace('~/$~', '', $data['url']));
			$retval = $bits[count($bits) -1];
			
			$this->debugPrint($bits, "bits for (". $id .")");
			$this->debugPrint($retval, "clean title for (". $id .")");
		}
		return $retval;
	}
	
	

	public static function makeCleanTitle($url) {
		$url = strtolower( $url );
		$url = preg_replace('~^/~', '', $url);
		$url = preg_replace('~/$~', '', $url);
		$url = str_replace( ' ', '-', trim( $url ) );
		$url = str_replace( '/', '-', trim( $url ) );
		$url = preg_replace( "/[^\/A-Za-z0-9_-]/", "", $url );
		$url = preg_replace('~-{2,}~', '-', $url);
		
		return $url;
	}
	
	
	public function getChildPages($parentId) {
		$sql = "SELECT 
				p.*,
				(
					select count(page_id) FROM pages p2 WHERE p2.parent_id=p.page_id
				) as _num_children
			FROM 
				pages as p
			WHERE p.parent_id=:pid";
		$params = array(
			'pid'	=> intval($parentId),
		);
		
		$this->db->run_query($sql, $params);
		$data = $this->db->farray_fieldnames($this->pkey);
//		$data = $this->db->fetch_array_assoc();
//		return $this->farray_fieldnames($indexField);
		
		return $data;
	}
	
	
	public static function getBodyPreview($body, $minWords=50) {
		$matches = array();
		preg_match_all('/<p[^\>]*>(.*)<\/p\s*>/i', $body, $matches);
		
		$useThis = array();
		if(intval($minWords) <= 0) {
			$minWords = 50;
		}
		$curWords = 0;
		
		
		$preview = $body;
		if(count($matches[0]) > 1) {
			foreach($matches[0] as $k=>$v) {
				$curWords += str_word_count($v);
				$useThis[$k] = $v;
				if($curWords >= $minWords) {
					break;
				}
			}
			$preview = implode($useThis);
		}
		
		return $preview;
	}
	
	
	public function getPageOptionsList($notPageId=null, $selectedId=null) {
		
		$allPages = $this->getAll();
		
		if(is_array($allPages) && count($allPages) > 0) {
			
			
			$sorted = array();

			$maxDepth = 1;
			$minDepth = 9;
			foreach ($allPages as $id => $data) {
				$sorted[$data['_depth']][$id] = $data;
				if($data['_depth'] > $maxDepth) {
					$maxDepth = $data['_depth'];
				}
				if($data['_depth'] < $minDepth) {
					$minDepth = $data['_depth'];
				}
			}

			// now re-order things (probably easier with array_multisort()) by the parent_id
			$magicList = array();
			foreach ($sorted as $depth => $recordList) {
				foreach ($recordList as $id => $data) {
					unset($data['body']);
					$magicList[$data['parent_id']][$id] = $data;
				}
			}
//			$this->debugPrint($magicList, "magically ordered list");
			
			$optionListData = array();
			foreach($magicList as $parentId=>$childData) {
				foreach($childData as $k=>$v) {
					$optionListData[$k] = $v;
				}
			}
			
			$optionList = $this->_buildNestedPageOptions($notPageId, '', 0, $selectedId, $optionListData);
		}
		
		return $optionList;
	}
	
	
	protected function _buildNestedPageOptions($page_id, $indent = '', $current_id = 0, $selected_id = 0, $pages=null) {
		$options = '';
		if(is_array($pages)) {
			foreach ($pages as $page) {
				if($page['parent_id'] == $current_id) {
					$options .= '<option value="' . $page['page_id'] . '"';
					if($selected_id == $page['page_id']) {
						$options .= ' selected="selected" ';
					}

					$titleLength = strlen($page['title']);
					if($titleLength > 30 && $titleLength) {
						$page['title'] = substr($page['title'], 0, 27) . '...';
					}

					$options .= '>' . $indent . ' ' . $page['title'] . '</option>' . "\n";
					
					// Recursion
					$subIndent = str_replace('-', '&nbsp;', $indent) . ' -';
					$options .= $this->_buildNestedPageOptions($page_id, $subIndent, $page['page_id'], $selected_id, $pages);
				}
			}
		}
		
		return $options;
	}
	
	

//	function getPageOptions($page_id, $indent = '', $current_id = 0, $selected_id = 0, $where = TRUE, $query = FALSE) {
//		global $db;
//
//		$options = '';
//		if(FALSE == $query) {
//			if(TRUE == $where)
//				$where = "WHERE page_id != {$page_id}";
//			else
//				$where = '';
//
//			$sql = <<<SQL
//					SELECT page_id, parent_id, title
//					FROM pages
//					{$where}
//					ORDER BY sort ASC, title
//SQL;
//					debugPrint($sql, __METHOD__ ." - sql");
//			$pages = $db->fetch_array($sql);
//		} else {
//			$pages = $query;
//		}
//
//		if(is_array($pages)) {
//			foreach ($pages as $page) {
//				if($page['parent_id'] == $current_id) {
//					$options .= '<option value="' . $page['page_id'] . '"';
//					if($selected_id == $page['page_id'])
//						$options .= ' selected="selected" ';
//
//					$titleLength = strlen($page['title']);
//					if($titleLength > 30 && $titleLength)
//						$page['title'] = substr($page['title'], 0, 27) . '...';
//
//					$options .= '>' . $indent . ' ' . $page['title'] . '</option>' . "\n";
//					// Recursion
//					$options .= $this->getPageOptions($page_id, str_replace('-', '&nbsp;', $indent) . ' -', $page['page_id'], $selected_id, FALSE, $pages);
//				}
//			}
//		}
//		debugPrint(htmlentities($options), __METHOD__ ." - returning");
//		return $options;
//	}
	
	
	public function getBreadcrumbs($id, $maxCrumbs = 10) {
		$crumbs = array();
		$pageData = $this->get($id);
		$pageData['itemclass'] = 'current';
		
		if(is_array($pageData) && count($pageData) > 0) {
			$this->debugPrint($pageData, "page data");
			$crumbs[] = $pageData;
			
			if(intval($pageData['parent_id'])) {
				$maxLoops = 10;
				if(intval($maxCrumbs) > 0) {
					$maxLoops = $maxCrumbs;
				}
				$morePageData = $pageData;
				$loops = 0;
				while(intval($morePageData['parent_id']) && $loops < $maxLoops) {
					$morePageData = $this->get($morePageData['parent_id']);
					$crumbs[] = $morePageData;
					$loops++;
				}
			}
		}
		else {
			throw new InvalidArgumentException("No data for page");
		}
		
		
		
		$defaultHome = array(
			'url'	=> '/',
			'title'	=> 'Home',
		);
		try {
			$home = $this->getHomepage();
			$this->debugPrint($home, "data for homepage");
			if(!is_array($home) || count($home) == 0) {
				$home = $defaultHome;
			}
		}
		catch (Exception $ex) {
			$this->debugPrint($ex->getMessage(), "could not retrieve homepage");
			$home = $defaultHome;
		}
		array_push($crumbs, $home);
		
		if(count($crumbs) > 1) {
			$crumbs = array_reverse($crumbs);
		}
		$this->debugPrint($crumbs, "crumbs");
		return($crumbs);
	}
	
	
	public function getParents($id) {
		$allData = $this->getAll();
		return parent::getParents($id, $allData);
	}
	
	
	/**
	 * Looks up all parent records to determine if there's a required group, 
	 * returning the value of the closest ancestor (if set).
	 * 
	 * @param type $pageId
	 */
	public function getParentRequiredGroupId($pageId) {
		$retval = null;
		$allParents = $this->getParents($pageId);
		$allPages = $this->getAll();
		
		foreach($allParents as $myId) {
			if(isset($allPages[$myId])) {
				if(intval($allPages[$myId]['required_group_id']) > 0) {
					$retval = $allPages[$myId]['required_group_id'];
					break;
				}
			}
		}
		
		return $retval;
	}
	
	
	public function getPageRequiredGroupId($pageId) {
		$groupId = null;
		
		$data = $this->get($pageId);
		if(isset($data['required_group_id'])) {
			$groupId = $data['required_group_id'];
		}
		else {
			$groupId = $this->getParentRequiredGroupId($pageId);
		}
		
		return $groupId;
	}
}
