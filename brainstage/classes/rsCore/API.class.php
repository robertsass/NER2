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
interface APIInterface {

	function init();
	function initPlugins();
	function getPlugins();
	function getFramework( $identifier, $newIfDoesNotExists );
	function getRequest();
	function getCommand();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class API extends CoreClass implements APIInterface {


	private $_Request;
	private $_frameworks;
	private $_output = array();
	protected $_QueryPath;
	protected $_delegate;
	protected $_command;

	private $_bufferOutput = true;
	private $_verbose = false;
	private $_dontDie = false;


	public function __construct( $handleRequest=true ) {
		$this->_Request = Core::getRequestPath();
		$this->_frameworks = array();
		$this->initPlugins();
		$this->init();

		header( 'Content-type: application/json' );

		if( $this->_verbose ) {
			$this->set( '_request', $this->_Request->getArray() );
			$this->set( '_cookies', $_COOKIE );
			$this->set( '_post', $_POST );
		}

		if( $this->_bufferOutput )
			ob_start();

		$result = null;
		if( $handleRequest ) {
			try {
				$result = $this->handleRequest();
				$this->set( 'success', ($result !== false) );
			} catch( \Exception $Exception ) {
				$this->set( 'success', false );
				$this->set( 'error', $Exception->getMessage() );
			}
		}

		if( $this->_bufferOutput && ob_get_length() > 0 )
			$this->set( 'result', ob_get_clean() );
		else
			$this->set( 'result', $result );

		if( $this->_verbose )
			$this->set( 'memory_allocated', memory_get_peak_usage(true) );

		$output = json_encode( $this->_output );
		if( isset( $_GET['callback'] ) )	// Support for JSONP
			$output = $_GET['callback'] .'('. $output .')';

		if( $this->_dontDie )
			echo $output;
		else
			die( $output );
	}


	/** Konstruktor-Erweiterung
	 */
	public function init() {
	}


	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access public
	 * @return void
	 */
	public function initPlugins() {
		foreach( $this->getPlugins() as $pluginName ) {
			$this->initPlugin( $pluginName );
		}
	}


	/** Lädt sämtliche Plugins
	 *
	 * @access public
	 * @return array
	 */
	public function getPlugins() {
		return \Autoload::getPlugins( false );
	}


	/** Initialisiert ein Plugin
	 *
	 * @access public
	 * @return boolean
	 */
	public function initPlugin( $pluginName ) {
		$APIFramework = new APIFramework( $this );
		try {
			if( is_callable( $pluginName .'::apiRegistration' ) ) {
				$pluginName::apiRegistration( $APIFramework );
				return true;
			}
		} catch( \Exception $Exception ) {
			\rsCore\ErrorHandler::catchException( $Exception );
		}
		return false;
	}


	/** Gibt das Framework zum angegebenen Indentifier zurück
	 *
	 * @param string $identifier
	 * @access public
	 * @final
	 * @return Framework
	 */
	final public function getFramework( $identifier=0, $newIfDoesNotExists=true ) {
		$identifier = strtolower( trim( str_replace( '\\', '/', $identifier ), '/' ) );
		if( $newIfDoesNotExists && !array_key_exists( $identifier, $this->_frameworks ) ) {
			$this->_frameworks[ $identifier ] = new Framework();
			$this->_frameworks[ $identifier ]->catchExceptions( false );
		}
		return $this->_frameworks[ $identifier ];
	}


	/** Gibt die Frameworks aller registrierten Indentifier zurück
	 *
	 * @access protected
	 * @final
	 * @return array
	 */
	final protected function getFrameworks() {
		return $this->_frameworks;
	}


	/** Gibt den RequestPath zurück
	 *
	 * @access public
	 * @final
	 * @return RequestPath
	 */
	final public function getRequest() {
		return $this->_Request;
	}


	/** Bestimmt, ob Ausgaben erst in einem OutputBuffer gesammelt werden sollen
	 *
	 * @param boolean $boolean
	 * @final
	 */
	final protected function bufferOutput( $boolean=true ) {
		$this->_bufferOutput = $boolean ? true : false;
	}


	/** Bestimmt, ob der Verbose-Modus aktiviert werden soll
	 *
	 * @param boolean $boolean
	 * @final
	 */
	final protected function verbose( $boolean=true ) {
		$this->_verbose = $boolean ? true : false;
	}


	/** Bestimmt, ob die Ausführung nach Abarbeitung der API terminieren soll
	 *
	 * @param boolean $boolean
	 * @final
	 */
	final protected function dontDie( $boolean=true ) {
		$this->_dontDie = $boolean ? true : false;
	}


	/** Setzt eine Ausgabevariable
	 *
	 * @param string $key
	 * @param string $value
	 * @param boolean $overwrite Bei false sind bereits gesetzte Variablen schreibgeschützt
	 * @final
	 */
	final protected function set( $key, $value, $overwrite=true ) {
		if( $overwrite || !array_key_exists( $key, $this->_output ) )
			$this->_output[ $key ] = $value;
	}


	/** Gibt den Wert einer Ausgabevariable zurück
	 *
	 * @param string $key
	 * @return string
	 * @final
	 */
	final protected function get( $key ) {
		if( array_key_exists( $key, $this->_output ) )
			return $this->_output[ $key ];
		return null;
	}


	/** Extrahiert den Pfad der API-Query
	 *
	 * @return string
	 */
	protected function getQueryPath() {
		if( !defined( 'BASE_SCRIPT_FILE' ) )
			return $this->getRequest()->path;
		if( $this->_QueryPath === null ) {
			$baseScriptFile = basename( BASE_SCRIPT_FILE );
			$pathSegments = $this->getRequest()->path;
			foreach( $pathSegments as $index => $pathSegment ) {
				unset( $pathSegments[ $index ] );
				if( $pathSegment == $baseScriptFile )
					break;
			}
			$this->_QueryPath = array_values( $pathSegments );
			if( $this->_verbose )
				$this->set( '_queryPath', $this->_QueryPath );
		}
		return $this->_QueryPath;
	}


	/** Extrahiert die aufzurufende API-Methode
	 *
	 * @return string
	 */
	protected function extractCommand() {
		$queryPath = $this->getQueryPath();
	#	if( count( $queryPath ) >= 2 )
			return array_pop( $queryPath );
	#	return current( $queryPath );
	}


	/** Extrahiert das zuständige Plugin
	 *
	 * @return string
	 */
	protected function extractDelegate() {
		$queryPath = $this->getQueryPath();
		$command = array_pop( $queryPath );
		if( count( $queryPath ) > 0 )
			return join( '/', $queryPath );
		return null;
	}


	/** Bestimmt die aufzurufende API-Methode
	 *
	 * @return string
	 */
	public function getCommand() {
		$command = $this->extractCommand() .'Action';

		if( $this->_verbose )
			$this->set( '_command', $command );

		return $command;
	}


	/** Ruft die API-Methode auf
	 *
	 * @return mixed
	 */
	protected function handleRequest() {
		$delegate = $this->extractDelegate();
		$command = $this->extractCommand();
		$params = $this->getRequest()->parameters;
		if( !$params || !is_array( $params ) )
			$params = array();

		if( $this->_verbose ) {
			$this->set( '_command', $command );
			$this->set( '_delegate', $delegate );
			$this->set( '_parameters', $params );
		}

		$params = array( $params );
		try {
			if( $delegate ) {
				$Framework = $this->getFramework( $delegate, false );
				if( $Framework ) {
					$result = $Framework->callHooks( $command, $params );
					if( is_array( $result ) && count( $result ) == 1 )
						return current( $result );
					return $result;
				}
				return false;
			}
			else
				return call_user_func_array( array($this, $this->getCommand()), $params );
		}
		catch( NotAuthorizedException $Exception ) {
			$this->set( 'error', $Exception->getMessage() );
			$this->set( 'trace', $Exception->getTraceAsString() );
			return false;
		}
		catch( NotPrivilegedException $Exception ) {
			$this->set( 'error', $Exception->getMessage() );
			$this->set( 'trace', $Exception->getTraceAsString() );
			return false;
		}
	}


}