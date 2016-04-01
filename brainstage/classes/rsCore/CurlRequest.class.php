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
interface CurlRequestInterface {

	function __construct( $url, $parameters, $requestType, $timeout, $acceptCookies );

	function getUrl();
	function getParameters();
	function setParameters( array $parameters );
	function getRequestType();
	function setRequestType( $type );
	function getTimeout();
	function setTimeout( $seconds );
	function getAcceptCookies();
	function setAcceptCookies( $boolean, $directory );
	function getUseragent();
	function setUseragent( $useragent );
	function getCookieDirectory();

	function getResponse();
	function getJson();
	function send();


}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CurlRequest extends CoreClass implements CurlRequestInterface {


	const DEFAULT_REQUEST_TYPE = 'GET';
	const DEFAULT_TIMEOUT = 20;
	const DEFAULT_COOKIE_DIR = 'curl/cookies/';
	const DEFAULT_VERIFY_SSL = false;


	private $_url;
	private $_parameters;
	private $_requestType;
	private $_timeout;
	private $_handleCookies;
	private $_useragent;
	private $_cookieDirectory;

	private static $_executionTime;


/* Private methods */

	protected static function getExecutionTime() {
		if( static::$_executionTime === null )
			static::$_executionTime = time();
		return static::$_executionTime;
	}


/* Public methods */

	/** Bereitet eine CURL-Anfrage vor
	 * @param string $url
	 * @param array $parameters
	 * @param string $requestType
	 * @param integer $timeout
	 * @param boolean $acceptCookies
	 * @return CurlRequest
	 * @api
	 */
	public function __construct( $url, $parameters=null, $requestType=null, $timeout=null, $acceptCookies=true ) {
		if( $parameters === null )
			$parameters = array();
		if( $requestType === null )
			$requestType = self::DEFAULT_REQUEST_TYPE;
		if( $timeout === null )
			$timeout = self::DEFAULT_TIMEOUT;
		$this->_url = $url;
		$this->setParameters( $parameters );
		$this->setRequestType( $requestType );
		$this->setTimeout( $timeout );
		$this->setAcceptCookies( $acceptCookies );
	}


	/** Gibt die gesetzte URL zurück
	 * @return string
	 * @api
	 */
	public function getUrl() {
		return $this->_url;
	}


	/** Gibt die gesetzten Parameter-Daten zurück
	 * @return array
	 * @api
	 */
	public function getParameters() {
		return $this->_parameters;
	}


	/** Setzt die Parameter-Daten
	 * @param array $parameters
	 * @return CurlRequest
	 * @api
	 */
	public function setParameters( array $parameters ) {
		$this->_parameters = $parameters;
		return $this;
	}


	/** Gibt den eingestellten HTTP Request-Typ zurück
	 * @return string
	 * @api
	 */
	public function getRequestType() {
		return $this->_requestType;
	}


	/** Setzt den HTTP Request-Typ
	 * @param string $type
	 * @return CurlRequest
	 * @api
	 */
	public function setRequestType( $type ) {
		$this->_requestType = strtoupper( $type );
		return $this;
	}


	/** Gibt den gesetzten Timeout zurück
	 * @return integer
	 * @api
	 */
	public function getTimeout() {
		return $this->_timeout;
	}


	/** Setzt das Timeout
	 * @param integer $seconds
	 * @return CurlRequest
	 * @api
	 */
	public function setTimeout( $seconds ) {
		$this->_timeout = intval( $seconds );
		return $this;
	}


	/** Gibt zurück, ob Cookies behandelt werden
	 * @return boolean
	 * @api
	 */
	public function getAcceptCookies() {
		return $this->_handleCookies;
	}


	/** Stellt ein, ob Cookies akzeptiert und behandelt werden
	 * @param boolean $boolean
	 * @param string $directory
	 * @return CurlRequest
	 * @api
	 */
	public function setAcceptCookies( $boolean, $directory=null ) {
		if( !is_string( $directory ) )
			$directory = self::DEFAULT_COOKIE_DIR;
		$this->_handleCookies = $boolean ? true : false;
		$this->_cookieDirectory = $directory;
		return $this;
	}


	/** Gibt den Useragent zurück
	 * @return string
	 * @api
	 */
	public function getUseragent() {
		return $this->_useragent;
	}


	/** Setzt den Useragent
	 * @param integer $seconds
	 * @return CurlRequest
	 * @api
	 */
	public function setUseragent( $useragent ) {
		$this->_useragent = $useragent;
		return $this;
	}


	/** Gibt zurück, in welchem Verzeichnis Cookies gespeichert werden
	 * @return string
	 * @api
	 */
	public function getCookieDirectory() {
		return rtrim( $this->_cookieDirectory, '/' ) .'/';
	}


	/** Sendet die Anfrage und gibt den Response Body zurück
	 * @return string
	 * @api
	 */
	public function getResponse() {
		return $this->send()->getResponse();
	}


	/** Sendet die Anfrage und gibt den Response Body als JSON dekodiert zurück
	 * @return mixed
	 * @api
	 */
	public function getJson() {
		return $this->send()->getJson();
	}


	/** Sendet die Anfrage und gibt das CurlResponse-Objekt zurück
	 * @return CurlResponse
	 * @api
	 */
	public function send() {
		$url = $this->getUrl();
		$parameters = $this->getParameters();
		$requestType = $this->getRequestType();

		if( $requestType == 'GET' && !empty( $parameters ) ) {
			$parameterString = array();
			foreach( $parameters as $key => $value )
				$parameterString[] = $key .'='. $value .'&';
			$parameterString = implode( '&', $parameterString );
			$url .= '?'. $parameterString;
		}

		if( $this->getAcceptCookies() ) {
			$cookieName = md5( session_id() . self::getExecutionTime() ) .'.txt';
			$cookieFilePath = $this->getCookieDirectory() . $cookieName;
			if( !is_file( $cookieFilePath ) ) {
				$f = fopen( $cookieFilePath, 'w' );
				if( $f )
					fclose( $f );
				else
					$this->setAcceptCookies( false );
			}
		}

		$curl = curl_init();

		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $curl, CURLOPT_AUTOREFERER, 1 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, $this->getTimeout() );
		curl_setopt( $curl, CURLOPT_VERBOSE, 1 );
	#	curl_setopt( $curl, CURLOPT_FAILONERROR, 1 );
	#	curl_setopt( $curl, CURLOPT_HEADER, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, self::DEFAULT_VERIFY_SSL );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, self::DEFAULT_VERIFY_SSL );

		if( $this->getAcceptCookies() ) {
			curl_setopt( $curl, CURLOPT_COOKIEJAR, $cookieFilePath );
			curl_setopt( $curl, CURLOPT_COOKIEFILE, $cookieFilePath );
		}

		if( $this->getUseragent() ) {
			curl_setopt( $curl, CURLOPT_USERAGENT, $this->getUseragent() );
		}

		if( $requestType == 'POST' ) {
			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $parameters );
		}

		$Response = new CurlResponse();
		$Response->url = $url;
		$Response->response = curl_exec( $curl );
		$Response->status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		$Response->error = curl_error( $curl );

		curl_close( $curl );
		return $Response;
	}


}