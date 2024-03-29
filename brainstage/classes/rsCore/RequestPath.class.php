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
interface RequestPathInterface {

	public static function parsePath( $url );

	public static function singleton();
	public static function getRequestPath();

	public static function joinParameters( array $keyValuePairs );
	public static function joinPathSegments( array $pathSegments );

	public function getRequestHandler();
	public function buildURL();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @todo parseRequest ist noch nicht unabhängig; bspw. getDomain() richtet sich nicht nach $url sondern greift selbst auf $_SERVER zu
 */
class RequestPath extends DataClass implements RequestPathInterface {


	private static $_singleton;


	private static function parseRequest( $url ) {
		$Request = new DataClass();
		$Request->path = static::getPathSegments( $url );
		$Request->parameters = static::getParameters( $url );
		$Request->domain = static::getDomain();
		$Request->scheme = static::getScheme();
		$Request->orig = static::buildRequestString( $Request->scheme, $Request->domain->orig, $Request->path, $Request->parameters );
		return $Request;
	}


	private static function getDomain() {
		$hostName = $_SERVER['HTTP_HOST'];
		$parts = explode( '.', $hostName );
		$Domain = new DataClass();
		$Domain->orig = $hostName;
		$Domain->tld = array_pop( $parts );
		$Domain->name = array_pop( $parts );
		$Domain->domainbase = $Domain->name .'.'. $Domain->tld;
		$Domain->subdomains = array_reverse( $parts );
		return $Domain;
	}


	private static function getScheme() {
		if( !array_key_exists('HTTPS', $_SERVER) || $_SERVER['HTTPS'] == '' )
			return 'http';
		return 'https';
	}


	private static function getParameters( $uri ) {
		$uriParts = explode( '?', $uri, 2 );
		$parameters = array();
		if( count( $uriParts ) > 1 ) {
			foreach( explode( '&', $uriParts[1] ) as $param ) {
				$pairs = explode( '=', $param, 2 );
				if( $pairs[0] != '' )
					$parameters[ $pairs[0] ] = isset( $pairs[1] ) ? urldecode( $pairs[1] ) : '';
			}
		}
		ksort( $parameters );
		return $parameters;
	}


	private static function getPathSegments( $uri ) {
		$uriParts = explode( '?', $uri, 2 );
		$pathPart = trim( $uriParts[0], '/' );
		$pathSegments = explode( '/', $pathPart );
		foreach( $pathSegments as $i => $pathSegment )
			if( $pathSegment == '' )
				unset( $pathSegments[$i] );
		return $pathSegments;
	}


	private static function buildRequestString( $scheme, $domain, array $pathSegments=null, array $parameterPairs=null ) {
		$requestString = $scheme .'://'. $domain;
		if( $pathSegments !== null && !empty( $pathSegments ) )
			$requestString .= static::joinPathSegments( $pathSegments );
		if( $parameterPairs !== null && !empty( $parameterPairs ) ) {
			$requestString = rtrim( $requestString, '/' );
			$requestString .= '/?'. static::joinParameters( $parameterPairs );
		}
		return $requestString;
	}


	public static function joinParameters( array $keyValuePairs, $urlEncode=true ) {
		$requestString = array();
		foreach( $keyValuePairs as $key => $val ) {
			if( $urlEncode ) {
				$key = urlencode( $key );
				$val = urlencode( $val );
			}
			$requestString[] = $key . ( $val != '' ? '='. $val : '' );
		}
		return join( '&', $requestString );
	}


	public static function joinPathSegments( array $pathSegments ) {
		return '/'. join( '/', $pathSegments );
	}


	public static function parsePath( $url ) {
		$isAbsolutePath = ( substr( $url, 0, 1 ) == '/' );
		$components = array();
		foreach( explode( '/', $url ) as $component ) {
			if( $component == '.' ) {
			}
			elseif( $component == '..' ) {
				array_pop( $components );
			}
			elseif( $component != '' ) {
				$components[] = $component;
			}
		}
		$reformedUrl = ($isAbsolutePath ? '/' : ''). implode( '/', $components );
		return $reformedUrl;
	}


	public static function singleton() {
		if( static::$_singleton === null )
			static::$_singleton = new static();
		return static::$_singleton;
	}


	public static function getRequestPath() {
		return static::singleton();
	}


	public function __construct() {
		$Request = static::parseRequest( $_SERVER['REQUEST_URI'] );
		$this->setData( $Request->getArray() );
	}


	public function getRequestHandler() {
		return RequestHandler::getHandlerByRequest( $this );
	}


	public function buildURL() {
		$Request = $this->getArray();
		return static::buildRequestString( $Request['scheme'], $Request['domain']->orig, $Request['path'], $Request['parameters'] );
	}


}