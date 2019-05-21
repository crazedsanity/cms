<?php
	$clean = array();
	$mysql = array();

	function recursiveClean( $values ) {

		$clean = array();
		$mysql = array();
		foreach ( $values as $ckey=>$cvalue ) {
			if ( is_array( $cvalue ) && count( $cvalue ) > 0 ) {
				foreach ( $cvalue as $ckey2=>$cvalue2 ) {
					if ( is_array( $cvalue2 ) ) {
						$avalues2 = array();
						// Recursion
						$avalues2 = recursiveClean( $cvalue2 );
						$clean[$ckey][$ckey2] = $avalues2['clean'];
						$mysql[$ckey][$ckey2] = $avalues2['mysql'];
					} else {
						if ( substr( $_SERVER['PHP_SELF'], 0, 7 ) == '/update' && $_SERVER['PHP_SELF'] != '/update/login.php' ) {
							$clean[$ckey][$ckey2] = filter_var( $cvalue2, FILTER_UNSAFE_RAW );
						} else {
							$clean[$ckey][$ckey2] = filter_var( $cvalue2, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
						}

						$mysql[$ckey][$ckey2] = @mysql_escape_string( $cvalue2 );
						if ( !$mysql[$ckey][$ckey2] ) {
							$mysql[$ckey][$ckey2] = addslashes( $cvalue2 );
						}
					}
				}
			} else {
				//$clean[$ckey] = $cvalue;
				if ( substr( $_SERVER['PHP_SELF'], 0, 7 ) == '/update' && $_SERVER['PHP_SELF'] != '/update/login.php' ) {
					$clean[$ckey] = filter_var( $cvalue, FILTER_UNSAFE_RAW );
				} else {
					$clean[$ckey] = filter_var( $cvalue, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				}
				if ( !$mysql[$ckey] ) {
					$mysql[$ckey] = addslashes( $cvalue );
				}
			}
		}
		return array( 'clean'=>$clean, 'mysql'=>$mysql );
	}

	function cleanURL( $url ) {
		#All URLs in DB should start and end with a /
		if ( $url[0]!='/' ) {
			$url = '/' . $url;
		}
		$urlStrLen = strlen( $url ) - 1;
		if ( $url[$urlStrLen] != '/' ) {
			$url .= '/';
		}
		return $url;
	}

	$avalues = array();
	$tempgetpost = array_merge( $_POST, $_GET );

	if ( is_array( $tempgetpost ) && count( $tempgetpost ) > 0 ) $avalues = recursiveClean( $tempgetpost );
	if ( isset( $avalues['clean'] ) ) $clean = $avalues['clean'];
	if ( isset( $avalues['mysql'] ) ) $mysql = $avalues['mysql'];
	
