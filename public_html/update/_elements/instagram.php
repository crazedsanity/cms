<?php

require_once("../../_app/core.php");


include_once('_app/classes/Instagram.class.php');

$instagram = new Instagram($db);

$instagram->exclude($clean['field'], $clean['value'], $clean['remove']);

header('Location: /update/instagrams/feed.php');
exit();

?>