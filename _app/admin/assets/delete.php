<?php 

use crazedsanity\core\ToolBox;
use cms\cms\core\assets;




$access=false;
if($acl->hasDelete($section)){
	$access=true;
}

if($access){
	if (isset($clean['asset_id'])) {
		$assetObj = new assets($db);
		$res = $assetObj->delete($clean['asset_id']);
		addAlert("Asset Deleted", "The asset was deleted ({$res})", "notice");
	}
	else {
		addAlert("Failure", "Not enough information given to delete the asset", "error");
	}
}
else {
	addAlert("Access Denied", "You don't have permission to do that.", "error");
}

ToolBox::conditional_header($sectionUrl);
exit;
