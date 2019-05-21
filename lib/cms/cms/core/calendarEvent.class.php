<?php


namespace cms\cms\core;

use \Exception;
use \InvalidArgumentException;

class calendarEvent extends core {
	
	public function __construct($db) {
		$this->db = $db;
		parent::__construct($db, 'calendar_events', 'calendar_event_id', 'title');
	}
	
	
	public function getEventsForTimespan($start, $end, array $calendars=null) {
		$retval = null;
		$sql = 'SELECT 
					ce.calendar_event_id, 
					ce.calendar_id, 
					ce.title, 
					ce.start_timestamp, 
					ce.duration, 
					addtime(start_timestamp, duration) AS end_timestamp,
					ce.allday, 
					ce.location, 
					rt.recurrence_type, 
					rt.interval,
					rt.interval,
					ce.recurrence_last_timestamp
			FROM 
				calendar_events AS ce
				INNER JOIN recurrence_types AS rt ON (ce.recurrence_type_id=rt.recurrence_type_id)
			WHERE	(
						( ce.start_timestamp <= :end1 AND addtime(ce.start_timestamp, ce.duration) >= :start1 )
						OR 
						(rt.recurrence_type <> :rtype AND ce.recurrence_last_timestamp > :rlts AND ce.recurrence_last_timestamp >= :start2 AND ce.start_timestamp < :end2)
					)
			';
					
		$params = array(
			'start1'	=> $start,
			'start2'	=> $start,
			'end1'		=> $end,
			'end2'		=> $end,
			'rtype'		=> 'none',
			'rlts'		=> '00:00:00'
		);
		
		
		if(is_array($calendars) && count($calendars)) {
			// manually create this part of the query (doesn't work as a parameter)
			$sql .= ' AND ce.calendar_id IN ('. join(',', $calendars) .')';
		}
		$sql .= ' ORDER BY ce.start_timestamp ASC';
		
		$this->debugPrint($sql, "SQL");
		$this->debugPrint($params, "params");
		
		$numrows = $this->db->run_query($sql, $params);
		if($numrows > 0) {
			$data = $this->db->farray_fieldnames();
			
			$retval = array();
			foreach($data as $k=>$v) {
				// TODO: add "cleantitle" column by cleaning "title" column like it was run through url().
				$v['cleantitle'] = generateSlug($v['title']);
				
				// TODO: add "end_timestamp" based on 
				$retval[$k] = $v;
			}
		}
		$this->debugPrint($retval, "returning events");
		
		return $retval;
	}
	
	
	public function getEvents($start, $end, array $calendars=null) {
		
		
		$events = $this->getEventsForTimespan($start, $end, $calendars);
		
		
		$calObj = new calendar($this->db);
		$calendars = $calObj->getAll();
		$colors = array();
		foreach($calendars AS $cal) {
			$colors[$cal['calendar_id']] = $cal['color'];
		}
		
		$endAsNum = strtotime($end);
		
		$items = array();
		foreach($events as $result) {
			$uniq = 0;

			$items = $this->_getItemRecurrences($result, $start, $end);
			
			// format each item.
			foreach ( $items as $i ) {
				$uniq++;
				$allDay = false;
				if ($i ['allday']) {
					$allDay = true;
				}

				if ($i ['recurrence_type'] == 'none') { // no need to convert dates if there was no recurrence
					$calStartDate = $i ["start_timestamp"];
					$calEndDate = $i ["end_timestamp"];
				} else { // convert dates because they recurred
					$calStartDate = date ( 'Y-m-d H:i:s', strtotime($i["start_timestamp"]));
					$calEndDate = date ( 'Y-m-d H:i:s', strtotime($i["end_timestamp"]));
				}


				$useTitle = $i['title'];
				$useAllDay = $allDay;
				if(isset($_GET['type']) && $_GET['type'] == 'colors') {
					$useTitle = '';
					$useAllDay = true; // this hack avoids the calendar showing times
				}
//				$calStartDate = strtotime($calStartDate);
//				$calEndDate = strtotime($calEndDate);

				$result['uniq'] = $uniq;
//				$result['itemdate'] = $i['itemdate'];
				
				$jsData = $result;
				$jsData['start_timestamp'] = $i['start_timestamp'];
				$jsData['start_date'] = explode(' ', $i['start_timestamp'])[0];
				$jsData['end_timestamp'] = $i['end_timestamp'];
				$jsData['end_date'] = explode(' ', $i['end_timestamp'])[0];
				$jsData['start_ts'] = strtotime($i['start_timestamp']);
				$jsData['end_ts'] = strtotime($i['end_timestamp']);
				
				$retval [] = array (
					'title' => $i['title'],
					'data' => $jsData,
					'start'	=> $i['start_timestamp'],
					'end'	=> $i['end_timestamp'],
					'allDay' => $useAllDay,
					'className' => 'colorblock gcal-default C' . $colors [$i ['calendar_id']] 
				); 
			}
		}
		
		return $retval;
	}
	
	public function get($id, array $constraints = null, $sql = null) {
		$sql = "SELECT *, addtime(start_timestamp, duration) AS end_timestamp FROM ". $this->table ." WHERE ". $this->pkey ."=:id";
		return parent::get($id, $constraints, $sql);
	}
	
	
	public function getForDate($id, $date) {
		$this->debugPrint(func_get_args(), "args");
		$record = $this->get($id);
		
		if(is_array($record) && count($record) > 0 && self::validateDate($date) === true) {
			$final = $record;

			$this->debugPrint($record, "record");
			
			// forge the given date into the record.
			$startBits = explode(' ', $record['start_timestamp']);
			$endBits = explode(' ', $record['end_timestamp']);
			
			
			$formatted = date('Y-m-d', strtotime($date));
			
			$final['start_timestamp'] = $formatted .' '. $startBits[1];
			$final['end_timestamp'] = $formatted .' '. $endBits[1];
			
			$final['start_ts'] = strtotime($record['start_timestamp']);
			$final['end_ts'] = strtotime($record['end_timestamp']);

//			$ts = strtotime($date);
//
//			$this->debugPrint($ts, "date (". $date .") as timestamp");
//
//			if(intval($record['recurrence_type_id']) > 0) {
//				// do things.
//				$_rtObj = new recurrenceType($this->db);
//				$rData = $_rtObj->get($record['recurrence_type_id']);
//
//				$this->debugPrint($rData, "recurrence info");
//				if(array_key_exists('interval', $rData)) {
//	//				$final = $data;
//					$this->debugPrint();
//				}
//			}
		}
		
		return $final;
	}
	
	
	/*
	 * Get all occurrences of a given record for a given time frame.
	 */
	protected function _getItemRecurrences(array $result, $start, $end) {
		$items = array();
		
		$current = strtotime($start);
		$last = strtotime($end);
		


//		$this->debugPrint($result, "data");
		
		if(array_key_exists('interval', $result)) {
			if(!empty($result['interval']) && $result['recurrence_type'] !== 'none') {
				$this->debugPrint(func_get_args(), "doing recurrence, argument list");
				
				$interval = $result['interval'];
				
				
				if(!empty($result['recurrence_last_timestamp'])) {
					$last = strtotime($result['recurrence_last_timestamp']);
				}
				if($current < strtotime($result['start_timestamp'])) {
					$current = strtotime($result['start_timestamp']);
				}
				$startBits = explode(' ', $result['start_timestamp']);
				

				$difference = strtotime ( $result ['end_timestamp'] ) - strtotime ( $result ['start_timestamp'] );

				$safety = 0;
				$current = $this->_getFirstOccurence($result, $start);
				$this->debugPrint ( $current, "last value=".$last."(" . date("Y-m-d H:i:s", $last) . "), current value" );
				$this->debugPrint ( date ( 'Y-m-d H:i:s', $current ), "(last=".date("Y-m-d H:i:s", $last).") current value as date/time" );
				
				
				
				while ( $current <= $last && $safety < 2000 ) {

					$thisitem = $result;
					
					

					$thisitem ['start_timestamp'] = date('Y-m-d', $current) .' '. $startBits[1];
					$thisitem ['end_timestamp'] = date('Y-m-d H:i:s', $current + $difference);
					$this->debugPrint($thisitem ['start_timestamp'], "incremented start (based on '". date ( 'Y-m-d H:i:s', $current ) ."')");
					$thisitem['itemdate'] = date('Y-m-d', $current);

					$items [] = $thisitem;

					$current = strtotime('+'. $interval, $current);
					$safety ++;
				}
				$fCurrent = date("Y-m-d H:i:s", $current);
				$fLast = date("Y-m-d H:i:s", $last);
				$this->debugPrint ( $items, "Items created from while loop (safety=" . $safety . ", current=". $current ."[".$fCurrent."], last=". $last ."[".$fLast."])" );
			}
			else {
				$items[] = $result;
				$this->debugPrint($items, "no recurrence, returning original item");
			}
		}
		else {
			$this->debugPrint(func_get_args(), "record is missing an interval...");
			throw new Exception("missing interval");
		}

		return $items;
	}
	
	
	protected function _formatEvents(array $items, array $colors) {
		$uniq = 0;
		$result = array();
		foreach ( $items as $i ) {
			$uniq++;
			$allDay = false;
			if ($i ['allday']) {
				$allDay = true;
			}

			$useAllDay = $allDay;

			$addThis = array (
				'title'		=> $i['title'],
				'data'		=> $result,
				'start'		=> $i['start_timestamp'],
				'end'		=> $i['end_timestamp'],
				'allDay'	=> $useAllDay,
				'color'		=> '#'. $colors[$i['calendar_id']],
				'textColor'	=> '#'. $colors[$i['calendar_id']],
				'className'	=> 'colorblock gcal-default C'. $colors[$i['calendar_id']],
				'uniq'		=> $uniq,
				'itemdate'	=> date('Y-m-d', strtotime($i['start_timestamp'])),
			); 
			$result[] = $addThis;
		}

		return $result;
	}
	
	
	/**
	 * Finds first occurrence for an item for the given range.  This should *always* 
	 * find a date.
	 * 
	 * @param type $record
	 * @param type $start
	 * @param type $end
	 */
	protected function _getFirstOccurence($record, $start) {
		$this->debugPrint(func_get_args(), "args");
		$retval = null;
		if(isset($record['start_timestamp']) && isset($record['interval'])) {
			$recordTs = strtotime($record['start_timestamp']);
			$startTs = strtotime($start);
			
			$maxLoops = 2000;
			$curLoops = 0;
			$myTs = $recordTs;
			if($startTs > $recordTs) {
				// date range is past the first instance of the record.  Increment until we get one.
				while($curLoops < $maxLoops) {
					$curLoops++;
					// increment based on interval.
					$myTs = strtotime('+'. $record['interval'], $myTs);
					if($myTs > $startTs) {
						$retval = $myTs;
//						$this->debugPrint(date('Y-m-d H:i:s', $myTs), "Found first instance after (". $curLoops .") loops");
						break;
					}
				}
				if(is_null($retval)) {
					$this->debugPrint(date("Y-m-d H:i:s", $myTs), "failed to get a timestamp, curLoop=(". $curLoops .")");
					throw new \LogicException("It all went to hell.");
				}
			}
			else {
				$retval = $recordTs;
			}
		}
		else {
			throw new InvalidArgumentException();
		}
		
		$this->debugPrint(date("Y-m-d H:i:s", $retval), "returning result");
		return $retval;
	}
	
	
	public static function validateDate($date) {
		$d = \DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') === $date;
	}
}
