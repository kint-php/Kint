<?php
/**
 * all base PHP variable types are processed here
 */
class Kint_Parsers_BaseTypes extends kintParser
{
	protected function _parse( & $variable )
	{ }


	private static function _isArrayTabular( $variable )
	{
		foreach ( $variable as $row ) {
			if ( is_array( $row ) && count( $row ) > 1 ) {
				if ( isset( $keys ) ) {
					if ( $keys === array_keys( $row ) ) { // two rows have same keys in a row? Close enough.
						return true;
					}
				} else {

					foreach ( $row as $col ) {
						if ( !is_scalar( $col ) && $col !== null ) {
							break 2;
						}
					}

					$keys = array_keys( $row );
				}
			} else {
				break;
			}
		}

		return false;
	}


	private static $_marker = null;

	protected function _parse_array( &$variable, $level = 0 )
	{
		isset( self::$_marker ) or self::$_marker = uniqid( "\x00" );

		$this->_type = 'array';
		$this->_size = count( $variable );


		if ( $this->_size === 0 ) {
			return;
		}
		if ( isset( $variable[self::$_marker] ) ) { // recursion; todo mayhaps show from which level
			$this->_value = self::$_marker;
			return false;
		}
		if ( self::$maxLevels !== 0 && $level > self::$maxLevels ) {
			$this->_value = "*DEPTH TOO GREAT*";
			return false;
		}


		$isSequential = self::_isSequential( $variable );

		$tabular = self::_isArrayTabular( $variable );
		if ( $tabular ) {

			$firstRow      = true;
			$extendedValue = '';
			$arrayKeys     = array();


			// assure no values are unreported if an extra key appears in one of the lines
			foreach ( $variable as $row ) {
				// todo process all keys in _isArrayTabular()
				if ( !is_array( $row ) ) {
					$tabular = false;
					break;
				}
				$arrayKeys = array_unique( array_merge( $arrayKeys, array_keys( $row ) ) );

				if ( self::$keyFilterCallback ) {
					foreach ( $arrayKeys as $k => $key ) {
						if ( call_user_func( self::$keyFilterCallback, $key ) === false ) {
							unset( $arrayKeys[$k] );
						}
					}
				}
			}
		}


		if ( $tabular ) {
			$variable[self::$_marker] = true;
			$header = '<th></th>';
			foreach ( $variable as $rowIndex => &$row ) {
				if ( $rowIndex === self::$_marker ) continue;

				if ( isset( $row[self::$_marker] ) ) {
					$this->_value = "*RECURSION*";
					return false;
				}

				$output = '<th>' . ( $isSequential ? '#' . ( $rowIndex + 1 ) : $rowIndex ) . '</th>';
				
				foreach ( $arrayKeys as $key ) {
					if ( $firstRow ) {
						$header .= '<th>' . htmlspecialchars( $key ) . '</th>';
					}
						
					if ( !array_key_exists( $key, $row ) ) {
						$output .= '<td class="kint-empty"></td>';
						continue;
					}

					$var = kintParser::factory( $row[$key] );
					if ( $var->_value === self::$_marker ) {
						$this->_value = '*RECURSION*';
						return false;
					} elseif ( $var->_value === '*RECURSION*' ) {
						$output .= '<td class="kint-empty">' . Kint_Decorators_Concise::decorate( $var ) . '</td>';
					} else {
						$output .= '<td>' . Kint_Decorators_Concise::decorate( $var ) . '</td>';
					}

				}
                $firstRow = false;
				$extendedValue .= "<tr>{$output}</tr>";
			}
            
			$this->_extendedValue = "<table class=\"kint-report\"><tr>{$header}</tr>{$extendedValue}</table>";

		} else {
			$variable[self::$_marker] = TRUE;
			$extendedValue            = array();

			foreach ( $variable as $key => & $val ) {
				if ( $key === self::$_marker
					|| ( self::$keyFilterCallback && call_user_func( self::$keyFilterCallback, $key, $val ) === false )
				) {
					continue;
				}


				$output = kintParser::factory( $val, $isSequential ? null : "'{$key}'", $level + 1 );
				if ( $output->_value === self::$_marker ) {
					$this->_value = "*RECURSION*"; // recursion occurred on a higher level, thus $this is recursion
					return false;
				}
				if ( !$isSequential ) {
					$output->_operator = '=>';
				}
				$extendedValue[] = $output;
			}
			$this->_extendedValue = $extendedValue;
		}

		unset( $variable[self::$_marker] );
	}

	protected function _parse_boolean( &$variable, $level = 0 )
	{
		$this->_type  = 'bool';
		$this->_value = $variable ? 'TRUE' : 'FALSE';
	}

	protected function _parse_double( &$variable, $level = 0 )
	{
		$this->_type  = 'float';
		$this->_value = $variable;
	}

	protected function _parse_integer( &$variable, $level = 0 )
	{
		$this->_type  = 'integer';
		$this->_value = $variable;
	}

	protected function _parse_null( &$variable, $level = 0 )
	{
		$this->_type = 'NULL';
	}

	private static $_objects;
	private static $_objectColor;
	private static $_objectId;
	private static $_ID_COUNTER = 97; // 'a'
	

	protected static function recursionMarker($hash) {
	    if (!isset(self::$_objectId[$hash])) {
    	    $randomColor = rand(0x0, 0x888888);
    	    self::$_objectColor[$hash] = sprintf("#%06x",$randomColor);
	        self::$_objectId[$hash] = chr(self::$_ID_COUNTER++);
	    }
	     
	    return "<a href=\"#"        . self::$_objectId[$hash]
    	    . "\"  style=\"color:"  . self::$_objectColor[$hash]
    	    ."\"><dt>    {RECURSION: #" . self::$_objectId[$hash] . "}</dt></a>";
	}
	
	protected function _parse_object( &$variable, $level = 0 )
	{     
		// copy the object as an array
		$array = (array)$variable;
		$hash  = spl_object_hash( $variable );


		$this->_type    = 'object';
		$this->_subtype = get_class( $variable );
		$this->_size    = count( $array );

		if ( isset( self::$_objects[$hash] ) ) {
            $this->_value = static::recursionMarker($hash);
			return false;
		} elseif  ( self::$maxLevels !== 0 && $level > self::$maxLevels )  {
			$this->_value = "*DEPTH TOO GREAT*";
			return false;
		}

		self::$_objects[$hash] = TRUE;


		if ( empty( $array ) ) return;


		$extendedValue = array();
		foreach ( $array as $key => & $value ) {		    
			if ( self::$keyFilterCallback
				&& call_user_func_array( self::$keyFilterCallback, array( $key, $value ) ) === false
			) {
				continue;
			}

			/* casting object to array:
			 * integer properties are inaccessible;
			 * private variables have the class name prepended to the variable name;
			 * protected variables have a '*' prepended to the variable name.
			 * These prepended values have null bytes on either side.
			 * http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
			 */
			if ( $key[0] === "\x00" ) {

				$access = $key[1] === "*" ? "protected" : "private";

				// Remove the access level from the variable name
				$key = substr( $key, strrpos( $key, "\x00" ) + 1 );
			} else {
				$access = "public";
			}

		
			$key               = self::_escape( $key );
			$output            = kintParser::factory( $value, $key, $level + 1 );
			$output->_access   = $access;
			$output->_operator = '->';
			$extendedValue[]   = $output;
		}
		$this->_extendedValue = $extendedValue;
		if (isset(self::$_objectId[$hash]))
	        $this->_id = self::$_objectId[$hash];
		if (isset(self::$_objectColor[$hash]))
	        $this->_color = self::$_objectColor[$hash];
		
		unset( self::$_objects[$hash] );
	}

	protected function _parse_resource( &$variable, $level = 0 )
	{
		$this->_type    = 'resource';
		$this->_subtype = get_resource_type( $variable );

		if ( $this->_subtype === 'stream' && $meta = stream_get_meta_data( $variable ) ) {

			if ( isset( $meta['uri'] ) ) {
				$file = $meta['uri'];

				if ( function_exists( 'stream_is_local' ) ) {
					// Only exists on PHP >= 5.2.4
					if ( stream_is_local( $file ) ) {
						$file = self::_debugPath( $file );
					}
				}

				$this->_value = $file;
			}
		}
	}

	protected function _parse_string( &$variable, $level = 0 )
	{
		$this->_type = 'string';

		if ( is_callable( $variable ) ) {
			$this->_subtype = '[callable]';
		} elseif ( function_exists( 'mb_detect_encoding' ) ) {
			$subtype = mb_detect_encoding( $variable );
			if ( $subtype !== 'ASCII' ) {

				$this->_subtype = $subtype;
			}
		}

		$this->_size    = self::_strlen( $variable, 'UTF-8' );
		$strippedString = self::_stripWhitespace( $variable );
		if ( $this->_size > self::$maxStrLength ) {

			// encode and truncate
			$this->_value         = '&quot;' . self::_escape( self::_substr( $strippedString, 0, self::$maxStrLength ) ) . '&nbsp;&hellip;&quot;';
			$this->_extendedValue = self::_escape( $variable );

		} elseif ( $variable !== $strippedString ) { // omit no data from display

			$this->_value         = '&quot;' . self::_escape( $variable ) . '&quot;';
			$this->_extendedValue = self::_escape( $variable );
		} else {
			$this->_value = '&quot;' . self::_escape( $variable ) . '&quot;';
		}
	}

	protected function _parse_unknown( &$variable, $level = 0 )
	{
		$this->_type    = "UNKNOWN";
		$this->_subtype = gettype( $variable );
		$this->_value   = var_export( $variable, true );
	}
}
