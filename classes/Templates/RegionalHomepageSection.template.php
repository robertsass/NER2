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
interface RegionalHomepageSectionInterface {

	public function extendOnepage( \rsCore\Container $Container );
	public function buildOnepageSection( \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class RegionalHomepageSection extends Base implements RegionalHomepageSectionInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendOnepage' );
	}


	/** Hook zum Erweitern der Homepage durch eine Onepage-Sektion
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendOnepage( \rsCore\Container $Container ) {
		$this->buildOnepageSection( $Container );
	}


	/** Baut die Onepage-Sektion
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return \rsCore\Container $Container
	 */
	public function buildOnepageSection( \rsCore\Container $Container ) {
		$this->buildPageContent( $Container );
	}


}