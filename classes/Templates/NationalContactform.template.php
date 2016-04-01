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
interface NationalContactformInterface {
}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class NationalContactform extends NationalBase implements NationalContactformInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendContentArea' );
	}


	/** Hook zum Erweitern des Contentst
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContentArea( \rsCore\Container $Container ) {
		return $this->buildContactForm( $Container );
	}


}