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
class Exception extends \Exception {}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class ClassNotFoundException extends Exception {


	public function __construct( $className, $message=null ) {
		$message = 'Class "'. $className .'" not found.'. ($message ? ' ('. $message .')' : '');
		parent::__construct( $message );
	}


}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class NotAuthorizedException extends Exception {


	public function __construct( $message=null ) {
		$message = 'Not authorized.'. ($message ? ' ('. $message .')' : '');
		parent::__construct( $message );
	}


}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class NotPrivilegedException extends Exception {


	public function __construct( $message=null ) {
		$message = 'Not privileged.'. ($message ? ' ('. $message .')' : '');
		parent::__construct( $message );
	}


}