<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace rsCore;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface DataClassInterface {

	public function get( $key );
	public function set( $key, $value );
	public function __get( $key );
	public function __set( $key, $value );
	public function setData( array $data, $mergeData );
	public function getArray();
	public function __toString();
	public function getClone();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DataClass extends CoreClass implements DataClassInterface {


	private $_data;


	public function __construct() {
		$this->_data = array();
	}


	public function get( $key ) {
		if( !array_key_exists( $key, $this->_data ) )
			return null;
		return $this->_data[ $key ];
	}


	public function set( $key, $value ) {
		$this->_data[ $key ] = $value;
		return $this;
	}


	public function __get( $key ) {
		return $this->get( $key );
	}


	public function __set( $key, $value ) {
		return $this->set( $key, $value );
	}


	public function setData( array $data, $mergeData=false ) {
		if( !$mergeData )
			$this->_data = $data;
		else
			$this->_data = array_merge( $this->_data, $data );
		return $this;
	}


	public function getArray() {
		return $this->_data;
	}


	public function __toString() {
		$array = $this->getArray();
		foreach( $array as $k => $object ) {
			if( is_object( $object ) && is_a( $object, __CLASS__ ) )
				$array[ $k ] = $object->getArray();
		}
		return json_encode( $array );
	}


	public function getClone() {
		$Clone = new static();
		$Clone->setData( $this->getArray() );
		return $Clone;
	}


}