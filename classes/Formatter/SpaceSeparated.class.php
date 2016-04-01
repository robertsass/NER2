<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Formatter;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 * @internal
 */
interface SpaceSeparatedInterface {
	
	function __construct();
	function addColumn( $attributeName, $callable );

	function export( array $rows );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 */
class SpaceSeparated extends \rsCore\CoreClass implements SpaceSeparatedInterface {
	
	
	private $_attributes = array();
	private $_data;
	
	
	protected static function callCallable( $callable, array $params=array() ) {
		if( is_callable( $callable ) ) {
			if( is_array( $callable ) ) {
				return \rsCore\Core::callMethod( $callable[0], $callable[1], $params );
			}
			elseif( is_string( $callable ) ) {
				return forward_static_call_array( $callable, $params );
			}
		}
		return null;
	}


	public function __construct() {
	}


	public function addAttribute( $attributeName, $dataType, $callable ) {
		$this->_attributes[ $attributeName ] = array(
			'datatype' => $dataType,
			'callable' => $callable
		);
	}


	public function export( array $input ) {
		if( $this->_data === null ) {
			$data = array();
			foreach( $input as $i => $part ) {
				$row = array();
				foreach( $this->_attributes as $attributeName => $attribute ) {
					$value = self::callCallable( $attribute['callable'], array( $part, $input, $i ) );
					if( strtolower( $attribute['datatype'] ) == 'string' )
						$value = '"'. $value .'"';
					if( strtolower( $attribute['datatype'] ) == 'numeric' )
						if( $value == 0 )
							$value = '0';
					$row[] = $value;
				}
				$data[] = implode( ',', $row );
			}
			$this->_data = implode( "\n", $data );
		}

		$output[] = '@RELATION '. $this->_relation;
		$output[] = '';
		
		foreach( $this->_attributes as $attributeName => $attribute ) {
			$output[] = '@ATTRIBUTE "'. $attributeName .'" '. $attribute['datatype'];
		}
		$output[] = '';

		$output[] = '@DATA';
		$output[] = $this->_data;
		
		return implode( "\n", $output );
	}


}