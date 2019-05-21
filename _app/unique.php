<?php
$unique = md5(time().$_SERVER['REMOTE_ADDR']);
$_SESSION['unique'] = $unique;
?>