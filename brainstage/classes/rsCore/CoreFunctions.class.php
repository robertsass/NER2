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
interface CoreFunctionsInterface {

	static function singleton();

	function redirect( $url, $haltExecution );
	function arraysValueForKey( array $array, $key, $defaultValue );
	function arrayIsAssociative( array $array );
	function rewriteResourceUrl( $url );
	function readableFileSize( $bytes );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CoreFunctions implements CoreFunctionsInterface {


	private static $_singleton;


	public static function singleton() {
		if( static::$_singleton === null )
			static::$_singleton = new static();
		return static::$_singleton;
	}


	private function __construct() {}


	public function redirect( $url, $haltExecution=true ) {
		header( 'location: '. $url );
		if( $haltExecution )
			exit;
	}


	public function arraysValueForKey( array $array, $key, $defaultValue ) {
		return array_key_exists( $key, $array ) ? $array[ $key ] : $defaultValue;
	}


	/** Gibt true zurück, wenn das Array assoziativ ist, d.h. die Indizies explizit zugewiesen wurden
	 * @param array $array
	 * @return boolean
	 */
	public function arrayIsAssociative( array $array ) {
		$previousKey = -1;
		foreach( $array as $key => &$value ) {
			if( !is_int( $key ) || $key !== $previousKey+1 )
				return true;
			$previousKey = $key;
		}
		return false;
	}


	/** Schreibt die URL um, sodass ggf. die cookieless Domain verwendet wird
	 * @return string
	 */
	public function rewriteResourceUrl( $path ) {
		if( defined('RESOURCES_SUBDOMAINS') && substr( $path, 0, 1 ) == '/' && substr( $path, 0, 2 ) != '//' && strpos( $path, 'static/' ) !== false ) {
			$subdomains = array();
			foreach( explode( ',', RESOURCES_SUBDOMAINS ) as $subdomain )
				$subdomains[] = trim( $subdomain );
			$subdomain = $subdomains[ array_rand( $subdomains ) ];

			if( RESOURCES_SUBDOMAINS_PER_TYPE ) {
				$fileSuffix = @array_pop( explode( '.', $path ) );
				$subdomain = $fileSuffix .'.'. $subdomain;
			}

			$domain = $subdomain .'.'. rtrim( requestPath()->domain->domainbase, '/' );
			$path = trim( $path, '/' );
			$url = '//'. $domain .'/'. $path;
			return $url;
		}
		return $path;
	}


	/** Wandelt eine Bytefolge in eine knackige Bezeichnung um
	 * @return string
	 */
	public function readableFileSize( $bytes ) {
	    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	    for( $i = 0; $bytes >= 1024 && $i < count($units)-1; $bytes /= 1024, $i++ );
	    return round( $bytes, 2 ) .' '. $units[$i];
	}


}