<?php

namespace cms\cms\core;

class userGroup extends core {
	
	
	public function __construct($db) {
		parent::__construct($db, 'user_groups', 'user_group_id', 'group_id, user_id');
	}
}
