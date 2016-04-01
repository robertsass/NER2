<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace rsCore;

include_once( 'Exception.class.php' );


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface CoreInterface {

	public static function core();
	public static function functions();

	public static function getSiteDirectory();
	public static function getSiteUrl();

	public static function callMethod( $object, $methodName, array $params, $exceptions );

	public static function getUseragent();
	public static function getRequestPath();

	public function getRuntimeStart( $microtime );
	public function getRuntimeDuration( $precision );
	public function getGlobalVariable( $key );

	public function database( $databaseTableName );
	public function databaseTree( $databaseTableName );

	public function registerDatabaseDatasetHandler( $databaseTableName, $classNameOrInstance );
	public function unregisterDatabaseDatasetHandler( $databaseTableName, $classNameOrInstance );
	public function getDatabaseDatasetHandler( $databaseTableName );

	public function activateErrorHandler();

	public function getDictionary();
	public function setDictionary( Dictionary $Dictionary );
	public function setDictionaryLanguage( $languageCodeOrInstance );
	public function translate( $key, $comment="" );

	public static function httpGet( $url, $params );
	public static function httpPost( $url, $params );

	public function buildPage();
	public function getPageBuilder();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Core extends CoreClass implements CoreInterface {


	/* Variables */
	private static $_coreSingleton;
	private $_runtimeStart;
	private $_coreFramework;
	private $_databaseCoreFramework;
	private $_isInitialized;
	private $_dictionary;
	private $_pageBuilder;
	private $_globalVariables;


/* Static methods */

	/** Gibt die zentrale Core-Instanz zurück
	 * @api
	 * @return Core Core-Instanz
	 */
	public static function core() {
		if( static::$_coreSingleton === null ) {
			static::$_coreSingleton = new static();
			static::$_coreSingleton->lateInit();
		}
		return static::$_coreSingleton;
	}


	/** Gibt das Singleton der CoreFunctions zurück, welches diverse praktische Funktionen bereitstellt
	 * @api
	 * @return CoreFunctions CoreFunctions-Instanz
	 */
	public static function functions() {
		return CoreFunctions::singleton();
	}


	/** Gibt das Projekt-Verzeichnis zurück
	 * @api
	 * @return string
	 */
	public static function getSiteDirectory( $relativeToFile=null ) {
		if( $relativeToFile === true )
			$relativeToFile = defined( 'BASE_SCRIPT_FILE' ) ? constant( 'BASE_SCRIPT_FILE' ) : null;
		$baseDir = defined( 'BASE_SCRIPT_FILE' )
					? dirname( constant( 'BASE_SCRIPT_FILE' ) )
					: rtrim( dirname( __FILE__ ), '/' ) .'/../../';
		$siteDir = rtrim( $baseDir, '/' );
		if( strpos( $siteDir, 'brainstage' ) !== false )
			$siteDir .= '/../';
		$siteDir = RequestPath::parsePath( rtrim( $siteDir, '/' ) ) .'/';

		if( $relativeToFile !== null ) {
			$relativeDir = explode( '/', rtrim( $siteDir, '/' ) );
			$explodedPath = explode( '/', rtrim( dirname( $relativeToFile ), '/' ) );
			foreach( $relativeDir as $i => $component ) {
				if( $component == $explodedPath[ $i ] )
					unset( $relativeDir[ $i ], $explodedPath[ $i ] );
				else {
					$relativeDir = null;
					break;
				}
			}
			if( $relativeDir !== null ) {
				$relativeDir = array_reverse( $relativeDir );
				foreach( $explodedPath as $component )
					$relativeDir[] = '..';
				$relativeDir[] = '.';
				$relativeDir = implode( '/', array_reverse( $relativeDir ) ) .'/';
				$siteDir = $relativeDir;
			}
		}

		return $siteDir;
	}


	/** Gibt die Home-URL des Projekts zurück
	 * @api
	 * @return string
	 */
	public static function getSiteUrl() {
		$RequestPath = \rsCore\Core::core()->getRequestPath();

		$urlBase = $RequestPath->scheme .'://'. $RequestPath->domain->orig;

		$pathSegments = array();
		$baseScriptFile = basename( BASE_SCRIPT_FILE );
		foreach( $RequestPath->path as $index => $pathSegment ) {
			if( $pathSegment == $baseScriptFile )
				break;
			$pathSegments[] = $pathSegment;
		}
		end( $pathSegments );
		if( current( $pathSegments ) == 'brainstage' )
			array_pop( $pathSegments );

		$path = implode( '/', $pathSegments );

		return rtrim( $urlBase .'/'. $path, '/' ) .'/';
	}


	/** Ruft eine Methode auf einem Objekt auf
	 * @api
	 * @param object $object Objekt dessen Methode aufgerufen werden soll
	 * @param string $methodName Name der Methode, die aufgerufen werden soll
	 * @param array|null $params Array der zu übergebenden Parameter
	 * @param boolean|false $exceptions Wenn true, werden im Fehlerfall Exceptions geworfen statt null zurückzugegeben
	 * @return mixed Rückgabe des Methodenaufrufs
	 */
	public static function callMethod( $object, $methodName, array $params=null, $exceptions=true ) {
		if( $params === null )
			$params = array();
		if( method_exists( $object, $methodName ) )
			return call_user_func_array( array($object, $methodName), $params );
		if( $exceptions )
			throw new Exception( "Method `". $methodName ."` is not defined in class `". get_class($object) ."`." );
		return null;
	}


	/** Gibt Informationen über den Browser zurück
	 * @api
	 * @return DataClass DataClass-Objekt welches die Informationen enthält
	 */
	public static function getUseragent() {
		return Useragent::getObject();
	}


	/** Gibt Informationen über den Anfrage-Query zurück
	 * @api
	 * @return RequestPath RequestPath-Objekt, welches die Informationen enthält
	 */
	public static function getRequestPath() {
		return RequestPath::getRequestPath();
	}


/* Constructor & Initializer */

	private function __construct() {
		$this->init();
	}


	private function init() {
		$this->_runtimeStart = microtime(true);
		$this->_coreFramework = new CoreFramework();
		$this->_databaseCoreFramework = new CoreFramework();
	}


	public function lateInit() {
		if( $this->_isInitialized )
			return null;
		$this->_isInitialized = true;
		session_start();

		$this->_globalVariables = array(
			'GET' => $_GET,
			'POST' => $_POST,
			'COOKIE' => $_COOKIE,
			'SESSION' => $_SESSION,
			'REQUEST' => $_REQUEST,
			'SERVER' => $_SERVER,
		);

		$this->setDictionaryLanguage( Localization::getLanguage() );
	}


/* Getter */

	public function getRuntimeStart( $microtime=true ) {
		if( $microtime == false )
			return intval( $this->_runtimeStart );
		return $this->_runtimeStart;
	}


	public function getRuntimeDuration( $precision=2 ) {
		$duration = microtime(true) - $this->_runtimeStart;
		if( $precision === null )
			return $duration;
		return round( $duration, $precision );
	}


	public function getGlobalVariable( $key ) {
		if( array_key_exists( $key, $this->_globalVariables ) )
			return $this->_globalVariables[ $key ];
		return null;
	}


/* Database methods */

	/** Gibt einen Datenbank-Connector auf die gewünschte Tabelle zurück
	 * @api
	 * @param string $databaseTableName Name der Tabelle, auf der gearbeitet werden soll
	 * @return DatabaseConnector Instanz eines DatabaseConnector
	 */
	public function database( $databaseTableName=null ) {
		return Database::table( $databaseTableName );
	}


	/** Gibt einen erweiterten Datenbank-Connector auf eine Tabelle zurück, die ein NestedSet enthälft
	 * @api
	 * @param string $databaseTableName Name der Tabelle, auf der gearbeitet werden soll
	 * @return DatabaseNestedSet Instanz eines DatabaseNestedSet
	 */
	public function databaseTree( $databaseTableName ) {
		return DatabaseNestedSet::instance( $databaseTableName );
	}


/* Database Framework methods */

	/** Registriert einen DatabaseDatasetHandler für eine gegebene Tabelle
	 * @api
	 * @param string $databaseTableName Name der Tabelle, die durch den Handler repräsentiert werden soll
	 * @param mixed $classNameOrInstance Name oder Instanz der Klasse, die Datensätze der Tabelle repräsentieren soll
	 */
	public function registerDatabaseDatasetHandler( $databaseTableName, $classNameOrInstance ) {
		if( $databaseTableName instanceof DatabaseNestedSet )
			$databaseTableName = $databaseTableName->getDatabaseConnector();
		if( $databaseTableName instanceof DatabaseConnector )
			$databaseTableName = $databaseTableName->getTable();
		return $this->_databaseCoreFramework->registerHandler( $classNameOrInstance, $databaseTableName );
	}


	/** Entfernt einen DatabaseDatasetHandler für eine gegebene Tabelle
	 * @api
	 * @param string $databaseTableName Name der Tabelle, die durch den Handler repräsentiert wurde
	 * @param mixed $classNameOrInstance Name oder Instanz der Klasse, die Datensätze der Tabelle repräsentierte
	 */
	public function unregisterDatabaseDatasetHandler( $databaseTableName, $classNameOrInstance ) {
		if( $databaseTableName instanceof DatabaseNestedSet )
			$databaseTableName = $databaseTableName->getDatabaseConnector();
		if( $databaseTableName instanceof DatabaseConnector )
			$databaseTableName = $databaseTableName->getTable();
		return $this->_databaseCoreFramework->unregisterHandler( $classNameOrInstance, $databaseTableName );
	}


	/** Gibt den DatabaseDatasetHandler für die gegebene Tabelle zurück
	 * @api
	 * @param string $databaseTableName Name der Tabelle, die durch den Handler repräsentiert wurde
	 * @return CoreFrameworkHandlerFactory CoreFrameworkHandlerFactory-Instanz, mit der der zugehörige DatabaseDatasetHandler instanziiert werden kann
	 */
	public function getDatabaseDatasetHandler( $databaseTableName ) {
		if( $databaseTableName instanceof DatabaseNestedSet )
			$databaseTableName = $databaseTableName->getDatabaseConnector();
		if( $databaseTableName instanceof DatabaseConnector )
			$databaseTableName = $databaseTableName->getTable();
		return $this->_databaseCoreFramework->getFactory( $databaseTableName );
	}


	/** Registriert eigene Error-Handler
	 * @api
	 * @return void
	 */
	public function activateErrorHandler( $redirectUrl=null ) {
		ErrorHandler::activate( $redirectUrl );
	}


	/** Gibt das Dictionary-Objekt zurück
	 * @api
	 * @return Dictionary
	 */
	public function getDictionary() {
		return $this->_dictionary;
	}


	/** Setzt das Dictionary-Objekt
	 * @param Dictionary $Dictionary
	 * @api
	 * @return object Selbstreferenz
	 */
	public function setDictionary( Dictionary $Dictionary ) {
		try {
			if( $Dictionary )
				$this->_dictionary = $Dictionary;
		} catch( \Exception $Exception ) {}
		return $this;
	}


	/** Initialisiert das Dictionary-Objekt für die angegebene Sprache
	 * @param mixed $languageCodeOrInstance
	 * @api
	 * @return object Selbstreferenz
	 */
	public function setDictionaryLanguage( $languageCodeOrInstance ) {
		try {
			$Dictionary = new Dictionary( $languageCodeOrInstance );
			if( $Dictionary )
				$this->_dictionary = $Dictionary;
		} catch( \Exception $Exception ) {}
		return $this;
	}


	/** Liefert die Übersetzung zu einem Übersetzungsschlüssel
	 * @param mixed $key
	 * @param string $comment
	 * @api
	 * @return string
	 */
	public function translate( $key, $comment="" ) {
		if( $this->_dictionary ) {
			$Translation = $this->_dictionary->get( $key, $comment );
			if( $Translation )
				return $Translation->getTranslatedString();
		}
		return $key;
	}


	/** Sendet einen GET-Request an eine URL
	 * @param string $url
	 * @param array $parameters
	 * @return CurlResponse
	 * @api
	 */
	public static function httpGet( $url, $params=null ) {
		return Curl::get( $url, $params )->send();
	}


	/** Sendet einen POST-Request an eine URL
	 * @param string $url
	 * @param array $parameters
	 * @return CurlResponse
	 * @api
	 */
	public static function httpPost( $url, $params=null ) {
		return Curl::post( $url, $params )->send();
	}


	/** Startet eine PageBuilder-Instanz, sofern noch keine existiert
	 * @api
	 * @return void
	 * @todo An dieser Stelle wird explizit die PageBuilder-Klasse instanziiert. Es sollte aber möglich sein, im Projekt eine Erweiterung von PageBuilder zu benutzen. Die müsste also dann hier geladen werden
	 */
	public function buildPage() {
		if( !$this->_pageBuilder ) {
			try {
				$this->_pageBuilder = new PageBuilder();
				$this->_pageBuilder->build();
			} catch( \Exception $Exception ) {
				ErrorHandler::catchException( $Exception );
			}
		}
	}


	/** Gibt die PageBuilder-Instanz zurück, sofern eine existiert
	 * @api
	 * @return PageBuilder PageBuilder-Instanz oder null
	 */
	public function getPageBuilder() {
		return $this->_pageBuilder;
	}


}