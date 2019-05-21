<?php

require_once(__DIR__ .'/../_app/core.php' );


if(isset($_GET['url'])) {
	$templatefile = 'inside.html';
}
else {
	$templatefile = 'home.html';
}


require_once( __DIR__ .'/../_includes/main.php' );
