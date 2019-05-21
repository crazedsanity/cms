<?php

include_once('_app/core.php');

if($clean['itinerary_id'] && $clean['type']=='name'){
	$sql=<<<EOSQL
		SELECT i.title
		FROM itinerary i
		WHERE i.itinerary_id={$mysql['itinerary_id']}
EOSQL;

	$it = $db->query_first($sql);
	
	echo $it['title'];
	
}
else if($clean['itinerary_id']){
	$sql=<<<EOSQL
		SELECT i.title, p.place_id, p.category, p.sub_category, p.title, ip.day
		FROM itinerary i
		LEFT JOIN itinerary_places ip ON ip.itinerary_id = i.itinerary_id
		LEFT JOIN places p ON p.place_id = ip.place_id
		WHERE i.itinerary_id={$mysql['itinerary_id']}
		ORDER BY ip.sort
EOSQL;


	$places = $db->fetch_array($sql);
	
	$output=array();
	
	foreach($places as $place){
		$output[]=array('place_id'=>$place['place_id'], 'day'=>$place['day']);
	}
echo json_encode($output);
	

}



?>