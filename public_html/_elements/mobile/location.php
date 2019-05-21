<?php
session_start();
$_SESSION['location']=array();
$_SESSION['location']['lat']=$_GET['lat'];
$_SESSION['location']['lon']=$_GET['lon'];
var_dump($_SESSION['location']);
?>