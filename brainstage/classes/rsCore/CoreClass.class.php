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
 */
interface NotReflectable {}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class NotReflectableException extends Exception {}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface CoreClassInterface {

	static function getObjectIdCount();
	function getObjectId();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CoreClass implements CoreClassInterface, Reflectable {


	private static $_coreClassObjectIdCounter;
	private $_coreClassObjectId;


	final public function getReflection() {
		if( $this instanceof NotReflectable )
			throw new NotReflectableException();
		return new \ReflectionObject( $this );
	}


	final public function printReflection() {
		if( $this instanceof NotReflectable )
			throw new NotReflectableException();
		\ReflectionObject::export( $this );
	}


	final public function exportReflection() {
		if( $this instanceof NotReflectable )
			throw new NotReflectableException();
		\ReflectionObject::export( $this, true );
	}


	final public function getObjectId() {
		if( !$this->_coreClassObjectId ) {
			$this->_coreClassObjectId = self::getObjectIdCount();
			self::$_coreClassObjectIdCounter++;
		}
		return $this->_coreClassObjectId;
	}


	final public static function getObjectIdCount() {
		if( self::$_coreClassObjectIdCounter == null )
			self::$_coreClassObjectIdCounter = 1;
		return self::$_coreClassObjectIdCounter;
	}


}