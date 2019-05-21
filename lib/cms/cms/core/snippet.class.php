<?php


namespace cms\cms\core;

/**
 * Description of snippet
 *
 * @author danf
 */
class snippet extends core {
	
	public function __construct($db) {
		parent::__construct($db, 'snippets', 'snippet_id', 'code');
	}
}
