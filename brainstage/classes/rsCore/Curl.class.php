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
interface CurlInterface {

	static function get( $url, $parameters );
	static function post( $url, $parameters );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Curl extends CoreClass implements CurlInterface {


	/** Sendet einen GET-Request an eine URL
	 * @param string $url
	 * @param array $parameters
	 * @return CurlResponse
	 * @api
	 */
	public static function get( $url, $parameters=null ) {
		if( !$parameters )
			$parameters = array();
		return new CurlRequest( $url, $parameters, 'GET' );
	}


	/** Sendet einen POST-Request an eine URL
	 * @param string $url
	 * @param array $parameters
	 * @return CurlResponse
	 * @api
	 */
	public static function post( $url, $parameters=null ) {
		if( !$parameters )
			$parameters = array();
		return new CurlRequest( $url, $parameters, 'POST' );
	}


}