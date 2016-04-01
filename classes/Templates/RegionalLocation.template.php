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
interface RegionalLocationInterface {
}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class RegionalLocation extends Base implements RegionalLocationInterface {


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
		return $this->buildLocationDetails( $Container );
	}


	/** Baut die Location-Details
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildLocationDetails( \rsCore\Container $Container ) {
		$locations = $this->getCity()->getLocations();
		if( is_array( $locations ) ) {
			foreach( $locations as $Location ) {
				$LocationContainer = $Container->subordinate( 'div.location-details' );
				$LocationContainer->subordinate( 'a', array('name' => $Location->getPrimaryKeyValue()) );
				$LocationContainer->subordinate( 'h2', $Location->name );
				$LocationContainer->subordinate( 'p', $Location->getAddress() );
				$Map = $LocationContainer->subordinate( 'iframe.map', array('frameborder' => 0, 'src' => 'https://www.google.com/maps/embed/v1/place?key=AIzaSyDxfuD_wia-XbrDy-Pl-_hSf9b_CyTuBXQ&q='. urlencode( $Location->getAddress() )) );
			}
		}
	}


}