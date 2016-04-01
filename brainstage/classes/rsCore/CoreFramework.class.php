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
interface CoreFrameworkInterface {

	public function hasHandler( $competence );
	public function getHandler( $competence );
	public function getHandlerClassname( $competence );
	public function getHandlerInstance( $competence );

	public function registerHandler( $classNameOrInstance, $competence );
	public function unregisterHandler( $classNameOrInstance, $competence );

	public function getFactory( $competence );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CoreFramework implements CoreFrameworkInterface {


	private $_handler;
	private $_factories;


	/* Private methods */

	private function init() {
		if( !$this->_handler )
			$this->_handler = array();
		if( !$this->_factories )
			$this->_factories = array();
	}


	private function resolveClassname( $classNameOrInstance ) {
		if( is_object( $classNameOrInstance ) )
			$className = get_class( $classNameOrInstance );
		else
			$className = $classNameOrInstance;
		return $className;
	}


	/* Magic methods */

	public function __construct() {
		$this->init();
	}


	public function __get( $key ) {
		return $this->getHandler( $key );
	}


	public function __set( $key, $value ) {
		if( $value == 0 && $this->hasHandler( $key ) ) {
			$this->unregisterHandler( $key );
		}
		elseif( is_object( $value ) || class_exists( $value ) ) {
			$this->registerHandler( $value, $key );
		}
	}


	/* Public methods */

	public function hasHandler( $competence ) {
		return $this->getHandlerClassname( $competence ) !== null;
	}


	public function getHandler( $competence ) {
		return $this->getHandlerInstance( $competence );
	}


	public function getHandlerClassname( $competence ) {
		return array_key_exists( $competence, $this->_handler ) ? $this->_handler[ $competence ] : null;
	}


	public function getHandlerInstance( $competence ) {
		$className = $this->getHandlerClassname( $competence );
		return $className ? new $className() : null;
	}


	public function registerHandler( $classNameOrInstance, $competence ) {
		$className = $this->resolveClassname( $classNameOrInstance );
		if( !class_exists( $className, true ) )
			throw new ClassNotFoundException( $className );
		if( is_string( $competence ) )
			$this->_handler[ $competence ] = $className;
		else
			throw new Exception( "$"."competence must be string" );
	}


	public function unregisterHandler( $classNameOrInstance, $competence ) {
		$className = $this->resolveClassname( $classNameOrInstance );
		unset( $this->_handler[ $competence ] );
	}


	public function getFactory( $competence ) {
		if( !array_key_exists( $competence, $this->_factories ) )
			$this->_factories[ $competence ] = new CoreFrameworkHandlerFactory( $competence, $this );
		return $this->_factories[ $competence ];
	}


}