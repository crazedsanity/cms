<?php

namespace cms\cms\core;

class pagination {
	public static function getVars($totalRecords, $pageNum=1, $limit=50) {
		
		if(!isset($limit) || !is_numeric($limit) || $limit < 1) {
			$limit = 50;
		}
		
		$totalPages = round($totalRecords / $limit);
		
		$curPage = $pageNum;
		if($pageNum < 1) {
			$curPage = 1;
		}
		elseif($pageNum > $totalPages) {
			$curPage = $totalPages;
		}
		$logicalPage = $curPage -1;
		
		$nextPage = $curPage +1;
		$nextDisabled = "";
		$lastDisabled = "";
		if($nextPage > $totalPages) {
			$nextPage = null;
			$nextDisabled = "disabled";
			$lastDisabled = "disabled";
		}
		
		$prevPage = $pageNum -1;
		$firstDisabled = "";
		$prevDisabled = "";
		if($prevPage < 1) {
			$prevPage = null;
			$prevDisabled = "disabled";
			$firstDisabled = "disabled";
		}
		
		if($curPage == 1) {
			$firstDisabled = "disabled";
		}
		
		$offset = $logicalPage * $limit;
		
		$startRecord = $offset + 1;
		$endRecord = $startRecord + $limit;
		if($endRecord > $totalRecords) {
			$endRecord = $totalRecords;
		}
		
		$vars = array(
			'startRecord'		=> $startRecord,
			'endRecord'			=> $endRecord,
			'totalRecords'		=> $totalRecords,
			'curPage'			=> $curPage,
			'totalPages'		=> $totalPages,
			'nextPage'			=> $nextPage,
			'prevPage'			=> $prevPage,
			'limit'				=> $limit,
			'offset'			=> $offset,
			'firstDisabled'		=> $firstDisabled,
			'nextDisabled'		=> $nextDisabled,
			'prevDisabled'		=> $prevDisabled,
			'lastDisabled'		=> $lastDisabled,
		);
		
		return $vars;
	}
}
