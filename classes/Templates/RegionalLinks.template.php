<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Templates;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface RegionalLinksInterface {
	
	public static function buildLinksList( Base $Template, \rsCore\Container $Container );
	
}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class RegionalLinks extends Base implements RegionalLinksInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendContentArea' );
	}


	/** Hook zum Erweitern des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContentArea( \rsCore\Container $Container ) {
		return self::buildLinksList( $this, $Container );
	}


	/** Baut die Link-Liste
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return \rsCore\Container $Container
	 */
	public static function buildLinksList( Base $Template, \rsCore\Container $Container ) {
		return $Container;
	}


}