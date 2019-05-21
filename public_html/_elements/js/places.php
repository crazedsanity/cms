<?php


//error_reporting(-1);
//ini_set('display_errors', true);

require(__DIR__ .'/../../_app/core.php');
$output=<<<JS
		var places = new Array(); //corresponding array of place objects
		/*
		places[987]={
			name:'Theodore\'s Dining',
			category:'Eat',
			subcategory:'Fine Dining',
			dates:'start'
		};
		*/
JS;


$sql=<<<EOSQL
	SELECT p.address, p.place_id, p.title, url(p.title) AS clean, m.filename, p.special, p.sub_category, p.category, url(p.category) AS clean_category, url(p.sub_category) AS clean_subcategory, url(p.title) AS clean_title, p.typecode, p.lat, p.lng, p.start, p.end, p.hours
	FROM places p
	LEFT JOIN media m ON m.media_id = p.thumb_media_id
	{$where}
	ORDER BY p.priority ASC
EOSQL;

$places = $db->fetch_array($sql);

if(count($places)>0){


	foreach($places as $place){
			$datetype='start';
			//startendadultschildren
			
			if($place['category']=='Stay'){
				$datetype.='endadultschildren';
			}
			else if($place['special'] == 'musical'){
				$datetype.='adultschildren';
			}
			else if($place['special'] == 'bullypulpit'){
				$datetype.='adultschildren';
			}
			else if($place['special'] == 'fondue'){
				$datetype.='adultschildren';
			}
			
			if($place['filename']){
//				$img = urlencode('/_elements/thumb.php?i='.htmlentities($place['filename']).'&x=150&y=150');
				$img = '/_elements/thumb.php?i='.preg_replace("~'~", "\'", htmlentities($place['filename'])).'&x=150&y=150';
			}
			else{
				$img = '/_elements/img/generic.jpg';	
			}
			
			//$place['hours'] = strip_tags($place['hours']);
			
			$place['hours'] = str_replace(array("\r\n", "\r"), "<Br />", $place['hours']);
			
			$place['hours'] = str_replace("'", "\'", $place['hours']);
			

			//$place['hours'] = nl2br($place['hours']);
			
			$output.=<<<JS
				places[{$place['place_id']}]={
					name:"{$place['title']}",
					address: "{$place['address']}",
					category:'{$place['category']}',
					subcategory:'{$place['sub_category']}',
					start:'{$place['start']}',
					end:'{$place['end']}',
					dates:'{$datetype}',
					thumb: '{$img}',
					link: '/{$place['clean_category']}/{$place['clean_subcategory']}/{$place['clean_title']}',
					typecode: '{$place['typecode']}',
					id:{$place['place_id']},
					lat:{$place['lat']},
					lng:{$place['lng']},
					special:'{$place['special']}'
					,hours:'{$place['hours']}'
					
				};		
JS;
	
	}
}


//$output = str_replace("\n",'',$output);

$output = str_replace("\t",'',$output);


echo $output;

?>