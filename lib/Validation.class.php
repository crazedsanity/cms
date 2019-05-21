<?php

class Validation {
	var $results; 

	function __construct( $fields, $required ) {
		
		$this->fields = $fields;
		$this->required = $required;
		
		$this->msg['requiredmissing'] 	= 'Required field missing.';
		$this->msg['notstring']			= 'Invalid character in field.';
		$this->msg['notint']			= 'Please enter a valid number.';
		$this->msg['notposint']			= 'Please enter a number greater than zero.';
		$this->msg['notnegint']			= 'Please enter a number less than zero.';
		$this->msg['notemail']			= 'Please enter a valid email address.';
		$this->msg['notphone'] 			= 'Please enter a valid phone number.';
		$this->msg['notzipcode'] 		= 'Please enter a valid zip code.';
		
		$this->labelerrorclass 		= ' labelerror';
		$this->fielderrorclass 		= ' fielderror';
		$this->encodehtmlentities 	= true;
		$this->requirednote 		= '*&nbsp;';
		$this->notrequirenote 		= '';
		$this->requirednotes 		= $this->getRequiredNotations();
		
		$this->phonex 				= '/^[0-9]{10}$/';
		$this->zipcodex 			= '/^([0-9]{5})(-[0-9]{4})?$/i'; //55555-4444 or 55555
		$this->canadian_zipcodex 	= '/^([a-ceghj-npr-tv-z]){1}[0-9]{1}[a-ceghj-npr-tv-z]{1}[0-9]{1}[a-ceghj-npr-tv-z]{1}[0-9]{1}$/i'; //A1B2c5
	}

	function validate( $values ) {
		$this->results->msgs 	= array();
		$this->results->errors 	= array();
		$this->results->values 	= array();
		$this->results->mysql 	= array();
		
			foreach ( $this->fields as $field=>$type ) {
				$checked = $this->checkValue( $values[$field], $type, $this->required[$field]);
				
				$this->results->msgs[$field] = $checked['msg'];
				$this->results->errors[$field] = $checked['error'];
				$this->results->values[$field] = $checked['value'];
				$this->results->mysqls[$field] = $checked['mysql'];
			}
			
			$errorOutput = $this->getErrorOutput( $this->results->msgs, $this->results->errors );
			
			$this->results->overallMsg = $errorOutput->msg;
			$this->results->js = $errorOutput->js;
			$this->results->overallError = $errorOutput->error;
			
			return $this->results;
			
	}
	function checkValue( $value, $type = 'string', $required = 0 ) {
		$checked = array();
		$checked['msg'] = '';
		$checked['error'] = false;
				
		if ( $this->encodehtmlentities ) {
			$checked['value'] = htmlentities( $value );
			$checked['mysql'] = @mysql_escape_string( htmlentities( $value ) );
			
			if ( !$checked['mysql'] ) {
				$checked['mysql'] = addslashes( htmlentities( $value ) );
			}
		} else {
			$checked['value'] = $value;
			$checked['mysql'] = @mysql_escape_string( $value );
			
			if ( !$checked['mysql'] ) {
				$checked['mysql'] = addslashes( $value );
			}
		}
		
		if ( $required && ( !$value || $value === NULL || ( ( $type == 'multicheckbox' || $type == 'multiselect' ) && is_array( $value ) && count( $value ) <= 0 ) ) ) {
			$checked['value'] 	= $value;
			$checked['mysql'] 	= @mysql_escape_string( $value );
			$checked['msg']		= $this->msg['requiredmissing'];
			$checked['error'] 	= true;
		} else {
			if ( is_array( $value ) && ( $type == 'multicheckbox' || $type == 'multiselect' ) ) {
				if (count($value) > 6) {
					$checked['error'] = true;
					$checked['msg'] = "The maximum employment positions to choose is 6.";
				}
				foreach ( $value as $value2 ) {
					$checkedtype = $this->checkValueType($value2, $type);
					if ( $checkedtype['error'] ) {
						$checked['error'] = $checkedtype['error'];
						$checked['msg'] = $checkedtype['msg'];
					}
				}
			} else {
				if ( $value ) {
					$checkedtype = $this->checkValueType( $value, $type );
					
					$checked['error'] = $checkedtype['error'];
					$checked['msg'] = $checkedtype['msg'];
				}
			}
		}
		return $checked;
	}

	function checkValueType( $value, $type ) {
		$checked = array();
		switch ( $type ) {
			case 'multiselect': // multiselect and multicheckbox, temporary fix until implemented
				
			case 'multicheckbox':
			case 'string':
				if ( !is_string( $value ) ) {
					$checked['error'] = true;
					$checked['msg'] = $this->msg['notstring'];
				}
				break;
			case 'int':
			case 'posint':
			case 'negint':
				if ( !filter_var( $value, FILTER_VALIDATE_INT ) && $value ) {
					$checked['error'] = true;
					$checked['msg'] = $this->msg['notint'];
				} else {
					if ( $type == 'posint' && $value <= 0 ) {
						$checked['error'] = true;
						$checked['msg'] = $this->msg['notposint'];
					} else if ( $type == 'negint' && $value >= 0 ) {
						$checked['error'] = true;
						$checked['msg'] = $this->msg['notnegint'];
					}
				}
				break;
			case 'email':
				if ( !filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
					$checked['error'] = true;
					$checked['msg'] = $this->msg['notemail'];
				}
				break;
			case 'phone':
				$value = preg_replace( '/\D/', '', $value );
				//5552221234
				if ( !preg_match( $this->phonex, $value ) ) {
					$checked['error'] = true;
					$checked['msg'] = $this->msg['notphone'];
				}
				break;
			case 'zipcode':
				//A1B2c5 or 55555-4444 or 55555
				if ( !preg_match( $this->zipcodex, $value ) && !preg_match( $this->canadian_zipcodex, $value ) ) {
					$checked['error'] = true;
					$checked['msg'] = $this->msg['notzipcode'];
				}
				break;
		}
		
		return $checked;
	}
	function getErrorOutput( $msgs, $errors ) {
		$errorOutput 		= '';
		$errorOutput->msg 	= false;
		$errorOutput->error = false;
		$usedmsgs 			= array();
		$js 				= '<script type="text/javascript">';

		foreach ( $msgs as $field=>$msg ) {
			if ( $errors[$field] ) {
				$errorOutput->error = true;
				if ( !in_array( $msg, $usedmsgs ) ) {
					$errormsg .= $msg . '<br>';
					$usedmsgs[] = $msg;
				}
				$js .= <<<JAVASCRIPT
					if ( document.getElementById( "{$field}_label" ) != null ) {
						document.getElementById( "{$field}_label" ).className += " {$this->labelerrorclass}";
					}
					if ( document.getElementById( "{$field}" ) != null ) {
						document.getElementById( "{$field}" ).className += " {$this->fielderrorclass}";
					}
JAVASCRIPT;
			}
		}

		$js .= '</script>';
		
		$errorOutput->msg = $errormsg;
		$errorOutput->js = $js;
		
		return $errorOutput;
	}

	function getRequiredNotations() {
		foreach ( $this->fields as $field=>$type ) {
			if ( $this->required[$field] ) {
				$this->requirednotes[$field]=$this->requirednote;
			} else {
				$this->requirednotes[$field]=$this->notrequirednote;	
			}
		}
		
		return $this->requirednotes;
	}
}
