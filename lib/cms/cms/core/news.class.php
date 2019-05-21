<?php

namespace cms\cms\core;

use crazedsanity\core\ToolBox;

class news extends core {
	
	protected $db;
	public $debug=false;
	
	public function __construct(Database $db) {
		parent::__construct($db, 'news', 'news_id');
		$this->defaultOrder = 'start_date DESC, title';
	}
	
	
	public function get($newsId) {
		$sql = "SELECT 
			 n.* 
			,m.filename
			FROM 
				news AS n
				LEFT OUTER JOIN media AS m ON n.media_id=m.media_id
			WHERE n.news_id={$newsId}";
		
		return $this->db->query_first($sql);
	}
	
	
	public function getAll($orderBy=null, array $constraints=null, $sql=null) {
//		return $this->db->fetch_array_assoc($sql, 'news_id');
		
		$this->debugPrint(func_get_args(), "Arguments");
		if($sql == null || empty($sql)) {
			$sql = "SELECT 
				 n.* 
				,m.filename
				FROM 
					news AS n
					LEFT OUTER JOIN media AS m ON n.media_id=m.media_id";
		}
		
		$params = array();
		
		if(is_array($constraints)) {
			
			$constraintSql = '';
			foreach($constraints as $k=>$v) {
//				$paramKey = preg_replace('~\.~', '_', $k);
//				$constraintSql = ToolBox::create_list($constraintSql, "{$k} LIKE :{$paramKey}", " AND ");
//				$params[$paramKey] = $v;
				if(is_object($v) && preg_match('~constraint$~', get_class($v)) == 1) {
					$constraintSql = ToolBox::create_list($constraintSql, $k .' '. $v->render(), " AND ");
			$this->debugPrint($constraintSql, "sql after adding a constraint object");
				}
				else {
					$paramKey = preg_replace('~\.~', '_', $k);
					$constraintSql = ToolBox::create_list($constraintSql, "{$k} LIKE :{$paramKey}", " AND ");
					$params[$paramKey] = $v;
				}
			}
			$sql .= ' WHERE '. $constraintSql;
		}
		
		if(!is_null($orderBy)) {
			$sql .= " ORDER BY {$orderBy}";
		}
		elseif(!is_null($this->defaultOrder)) {
			$sql .= " ORDER BY {$this->defaultOrder}";
		}
		$this->debugPrint($sql, "SQL");
		$this->debugPrint($params, "Parameters");
		
		$this->db->run_query($sql, $params);
		
		// TODO: have an option/argument/something to make this call Database::farray() instead (to allow duplicates)
		return $this->db->farray_fieldnames($this->pkey);
	}
	
	
	public function addTag($id, $tag) {
		\debugPrint(func_get_args(), __METHOD__ ." - arguments");
		$tagObj = new \cms\tag($this->db);
		$info = $this->get($id);
		\debugPrint($info, __METHOD__ .' - info');
		return $tagObj->addTag($id, "/news/{$id}", $info['title'], 'news', $tag);
	}
}
