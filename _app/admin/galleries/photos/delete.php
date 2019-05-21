<?php 

use crazedsanity\core\ToolBox;

addAlert("Wrong URL", "You may have found an out-of-date link.  Please try again", "fatal");

$location = '/update/galleries/index.php';
ToolBox::conditional_header($location);
exit;