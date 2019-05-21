<?php

/* 
 * Creates a part of a query constraint.  When trying to use parameterized queries, 
 * allowing a method call to pass in the operator (e.g. "<=") is dangerous; 
 * constructiong a new object to be passed is safer.
 */

namespace cms\database;

class constraint {
	
	public $type = null;
	
	public $operator=null;
	
	const TYPE_DATE = 'date';
	
	const OPERATOR_LIKE		= 'LIKE';
	const OPERATOR_EQUALS	= '=';
	const OPERATOR_LTOE		= '<=';
	const OPERATOR_LT		= '<';
	const OPERATOR_GTOE		= '>=';
	const OPERATOR_GT		= '>';
	
	
	
	public function __construct($type, $operator=self::OPERATOR_LIKE, $value=null) {
		$this->type = $type;
		$this->operator = $operator;
		$this->value = $value;
	}
	
	
	public function render() {
//		switch($this->value) {
//			
//		}
		
		if($this->type == self::TYPE_DATE) {
			if($this->value === null) {
				$string = 'IS NULL';
			}
			else {
				$string = $this->operator .' '. $this->value;
			}
		}
		else {
			throw new \LogicException("Unknown type (". $this->type .")");
		}
		
		
		return $string;
//		return $this->type .' '. $this->operator .'';
	}
	
	
	public function __toString() {
		return $this->render();
	}
	
}

//$x = new constraint('date',	'<=', 'current_date()');

// To string:
//echo "$x";// outputs::: <= current_date()