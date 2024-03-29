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
interface DatabaseDatasetWrapperInterface {

	public function __construct( DatabaseDataset $Dataset );
	public function __toString();
	public function __call( $method, $params );
	public function __get( $key );
	public function __set( $key, $value );
	public function get( $key );
	public function set( $key, $value );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DatabaseDatasetWrapper extends CoreClass implements DatabaseDatasetWrapperInterface {


	/** @var DatabaseDataset */
	private $_Dataset;


	/* Private methods */

	protected function getDatabaseConnector() {
		return $this->_Dataset->getDatabaseConnector();
	}


	protected function setDataset( DatabaseDataset $Dataset ) {
		$this->_Dataset = $Dataset;
	}


	protected function getDataset() {
		return $this->_Dataset;
	}


	/* Public methods */

	public function __construct( DatabaseDataset $Dataset ) {
		$this->setDataset( $Dataset );
	}


	public function __toString() {
		return $this->_Dataset->__toString();
	}


	public function __call( $method, $params ) {
		return Core::callMethod( $this->_Dataset, $method, $params );
	}


	final public function __get( $key ) {
		return $this->get( $key );
	}


	final public function __set( $key, $value ) {
		return $this->set( $key, $value );
	}


	final public function get( $key ) {
		return $this->_Dataset->get( $key );
	}


	final public function set( $key, $value ) {
		return $this->_Dataset->set( $key, $value );
	}


}