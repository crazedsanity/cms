<?php


use crazedsanity\core\ToolBox;
use cms\cms\core\calendar;
use cms\cms\core\calendarEvent;
use cms\cms\core\recurrenceType;


if(!empty($_GET['debug'])) {
	header('Content-Type: text/html');
	ini_set('display_errors', true);
	ToolBox::$debugPrintOpt = 1;
	
	applicationLog('calendar', "Showing debug information... _GET:: ". print_r($_GET,true));
}
else {
	header('Content-Type: application/json');
}
require_once( "../../_app/core.php" );

$ceObj = new calendarEvent($db);
$calObj = new calendar($db);

switch( $clean['action'] ) {

	case "add-cal":
		if(isset($_POST['title']) && !empty($_POST['title']) && !empty(trim($clean['title']))) {
			$title = trim($clean['title']);
			
			if(is_array($calObj->colorArray)) {
				$color = $calObj->colorArray[ array_rand($calObj->colorArray)];
			}
			else {
				$color = '009900';
			}

			try {
				$insertData = array(
					'title' => $title,
					'color' => $color 
				);
				$id = $calObj->insert($insertData);
				applicationLog('calendar', "created a calendar, id=($id), data:: ". print_r($insertData,true));

				$result = array(
					'success' => true,
					'id'	=> $id,
					'title' => $title, 
					'color' => $color,
				);
			} catch (Exception $ex) {
				applicationLog('calendar', "exception=(". $ex->getMessage() ."), insertData::: ". print_r($insertData,true), print_r($ex,true));
				$result = array(
					'success'	=> false,
					'error'		=> $ex->getMessage(),
					'insertData'=> $insertData,
				);
			}
		}
		else {
			applicationLog('calendar', "empty title, POST::: ". print_r($_POST,true));
			$result = array(
				'success'	=> false,
				'error'		=> "missing title",
			);
		}

		break;

	case "event":
		
		$debug = array();

		if($clean['start_date']){
			$start_timestamp = $clean['start_date'];
			if($clean['start_time']){
				$start_timestamp.=$clean['start_time'];
			}
			$start_timestamp=strtotime($start_timestamp);
		}
		else{
			$start_timestamp=time();		
		}
		
		
		if(intval($start_timestamp) < 100) {
			// somehow they managed to get an empty date. So change it to start now instead.
			$evalTs = intval($start_timestamp);
			applicationLog('calendar', "invalid timestamp, original start_timestamp=($start_timestamp), final evalTs=($evalTs), clean data: ". print_r($clean,true));
			$start_timestamp=time();	
		}
		$duration = '01:00:00';
		
		if(!empty($clean['end_time'])) {
			if(!empty($clean['end_date'])) {
				$end_timestamp = strtotime($clean['end_date'] .' '. $clean['end_time']);
			}
			else {
				$end_timestamp = strtotime($clean['start_date'] .' '. $clean['end_time']);
			}
			$duration = $end_timestamp - $start_timestamp;
		}
		


		$event_id 					= !empty( $clean['id'] ) ? trim( $clean['id'] ) : 0;
		$calendar_id				= !empty( $clean['calendar_id'] ) ? trim( $clean['calendar_id'] ) : '';
		$title 						= !empty( $clean['title'] ) ? trim( $clean['title'] ) : '';
		$allday 					= !empty( $clean['allday'] ) && $clean['allday'] ? '1' : '0';
		$location					= !empty( $clean['location'] ) ? trim( $clean['location'] ) : '';
		$description				= !empty( $clean['description'] ) ? trim( $clean['description'] ) : '';
		
		$recurrence_type			= !empty( $clean['recurrence_type'] ) ? $clean['recurrence_type'] : 'none';
		
		$recurrence_last_timestamp 	= !empty( $clean['recurrence_last_timestamp'] ) ? strtotime( $clean['recurrence_last_timestamp'] ) : '';


		$thisevent= array(
					'title' 					=> $title,
					'calendar_id'				=> $calendar_id,
					'start_timestamp' 			=> date( "Y-m-d H:i:s", $start_timestamp ),
					'duration'					=> get_hours($duration),
					'allday' 					=> $allday,
					'location'					=> $location,
					'description'				=> $description,
				);
		if(!empty($recurrence_type)) {
			$thisevent['recurrence_type_id'] = $recurrence_type;
		}
		if(empty($recurrence_last_timestamp)) {
			if(!empty($recurrence_type)) {
				// they've specified recurrence, but not an end date.  Do it for them.
				$rtObj = new recurrenceType($db);
				$rInfo = $rtObj->get($recurrence_type);
				
				$debug['recurrence_info'] = $rInfo;
				$debug['recurrence_info']['_start_timestamp'] = $thisevent['start_timestamp'];
				
				$thisevent['recurrence_last_timestamp'] = date('Y-m-d H:i:s', strtotime('+'.$rInfo['default_recurrence_end'], strtotime($thisevent['start_timestamp'])));
			}
			else {
				// no recurrence, but field can't be null: set it to the start timestamp.
				$debug['recurrence_lt'] = "using start timestamp::: ". $start_timestamp;
				$thisevent['recurrence_last_timestamp'] = date( "Y-m-d H:i:s", $start_timestamp );
			}
		}
		else {
			$debug['recurrence_lt'] = "using sent value::: ". $recurrence_last_timestamp;
			$thisevent['recurrence_last_timestamp'] = date( "Y-m-d H:i:s", $recurrence_last_timestamp );
		}
		$debug['thisevent'] = $thisevent;


		if($event_id>0){
			$id = false;
			try {
				$updateRes = $ceObj->update($thisevent, $event_id);
				$id = $event_id;
				applicationLog('calendar', "event updated, updateRes=($updateRes), thisevent:: ". print_r($thisevent,true));
			}
			catch(Exception $ex) {
				$thisevent['debug'] = $ex->getMessage();
				$thisevent['error'] = $ex->getMessage();
				applicationLog('calendar', "Exception while trying to update event, thisevent:: ". print_r($thisevent, true), print_r($ex,true));
			}
		}
		else{
			try {
				$id = $ceObj->insert($thisevent);
				applicationLog('calendar', "event created, id=($id), thisevent:: ". print_r($thisevent,true));
			}
			catch(Exception $ex) {
				$thisevent['error'] = $ex->getMessage();
				applicationLog('calendar', "Exception while trying to create event, thisevent:: ". print_r($thisevent, true), print_r($ex,true));
			}
		}			


		$result = $thisevent;
		$result['debug'] = $debug;
		$result['id']=$id;




		break;

	case "del-cal":
		if(isset($_POST['id']) && intval($_POST['id']) > 0) {
			$id = intval($_POST['id']);
			try {
				$delRes = $calObj->delete(intval($_POST['id']));
				$result = array( 'success' => true );
				
				// also delete items.
				$itemDelRes = $ceObj->deleteWhere(array('calendar_id'=>$id));
				
				applicationLog('calendar', "deleted calendar, delRes=($delRes), itemDelRes=($itemDelRes)");
			} catch (Exception $ex) {
				$result = array(
					'success'	=> false,
					'error'		=> $ex->getMessage(),
				);
				applicationLog('calendar', "Exception trying to delete calendar (id=$id), POST:: ". print_r($_POST,true), print_r($ex,true));
			}
		} else {
			applicationLog('calendar', "no calendar to delete... POST:: ". print_r($_POST, true));
			$result = array( 'success' => false );
		}
		break;

	case "update-cal":
		if(isset($_POST['id']) && intval($_POST['id']) > 0) {
			$id = intval($_POST['id']);
			$params = array();
			
			if(!empty($clean['title'])) {
				$params['title'] = trim($clean['title']);
			}
			if(!empty($clean['color'])) {
				$params['color'] = trim( $clean['color'] );
			}
			if(empty($params['title'])) {
				unset( $params['title'] );
			}

			try {
				$updateRes = $calObj->update($params, $id);
//				$db->update( 'calendars', $params, "`id`='{$id}'" );
				$result = $params;
				$result['success'] = true;
				$result['id'] = $id;
				applicationLog('calendar', "updated id=($id) successfully, params:: ". print_r($params,true));
			} catch (Exception $ex) {
				$result = array(
					'success'	=> false,
					'error'		=> $ex->getMessage(),
					'sqldata'	=> $params,
				);
				applicationLog('calendar', "failed to update calendar, params:: ". print_r($params, true), print_r($ex,true));
			}
		} else {
			$result = array( 'success' => false );
			applicationLog('calendar', "falied to update calendar, missing ID... POST:: ". print_r($_POST,true));
		}
		break;

	case "getevent":

		if($clean['id']){
			$result = $ceObj->get($clean['id']);
			
			$result['start_date']=date('n/j/Y', strtotime($result['start_timestamp']));
			$result['start_time']=date('g:i a', strtotime($result['start_timestamp']));

			$result['end_date']=date('n/j/Y', strtotime($result['end_timestamp']));
			$result['end_time']=date('g:i a', strtotime($result['end_timestamp']));

		}
		else{
			$result = array( 'success' => false );
		}

		break;
	case "deleteevent":
		
		if(isset($_POST['id']) && intval($_POST['id']) > 0) {
			try {
				$delRes = $ceObj->delete(intval($_POST['id']));
				$result = array(
					'success'	=> true,
				);
			} catch (Exception $ex) {
				$result = array(
					'success'	=> false,
					'error'		=> $ex->getMessage(),
				);
			}
		}
		else {
			$result = array('success' => false);
		}

			break;
	default:
		$result = array(
			'success'	=> false,
			'action'	=> $clean['action'],
			'error'		=> "Unknown action",
		);
		applicationLog('calendar', "unknown action... POST:: ". print_r($_POST,true));
		break;
}


if(!debugPrint($result, "result")) {
	echo json_encode( $result );
}
exit;

/**
 * Convert seconds into a timeframe like "01:23:00" (ignoring extra seconds).
 * 
 * @param int $seconds
 * @return string
 */
function get_hours($seconds) {
	if(!empty($seconds) && intval($seconds) > 0) {
		
		$hours = (int) ($seconds / 3600);
		$minutes = (int) (($seconds - $hours * 3600) / 60);

		$return = str_pad($hours, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes, 2, '0', STR_PAD_LEFT) .':00';

		return $return;
	}
	else {
		// give it a default of 1 hour.
		return '00:00:00';
	}
}