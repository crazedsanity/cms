<?php

require_once("../../_app/core.php");


if($clean['photo_id'] && ($clean['include']=='0' || $clean['include']=='1') ){

	$sql=<<<EOSQL
		UPDATE instagram_cache2 SET include = {$mysql['include']} WHERE photo_id = '{$mysql['photo_id']}'
EOSQL;

	$db->query($sql);
}

header('Location: /update/instagrams/feed2.php#'.$clean['photo_id']);
exit();

?>