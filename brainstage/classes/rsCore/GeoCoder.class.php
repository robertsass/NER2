<?php	/* GeoCoder 1.0 */

class GeoCoder {


	const GEO_SERVICE_URL = 'http://brainedia.de/geolocate';


	private static $cache = null;


	private static function init_cache() {
		if( self::$cache !== null )
			return true;
		self::$cache = array();
		return true;
	}


	private static function build_query( array $params ) {
		$query = array();
		foreach( $params as $param => $value )
			$query[] = urlencode( $param ) .'='. urlencode( $value );
		return self::GEO_SERVICE_URL .'?'. implode( '&', $query );
	}


	private static function geocode( array $params ) {
		$queryUrl = self::build_query( $params );
		$result = rsCurl::http_get( $queryUrl );
		return json_decode( $result->response );
	}


	public static function geocodeAddress( $address ) {
		self::init_cache();
		$params = array( 'address' => $address );
		return self::geocode( $params );
	}


}