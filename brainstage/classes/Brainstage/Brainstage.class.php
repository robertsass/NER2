<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface BrainstageInterface {

	static function getPlugins();
	static function registerPrivileges( $pluginName, $privileges );
	static function getPluginPrivileges();
	static function getInitializedPlugins();

	function getFramework();
	function getRequest();
	function getRequestPath();
	function getTemplate();

	function registerHook( $Object, $event );
	function unregisterHook( $Object, $event );
	function callHooks( $event, $params );

	static function translate( $translationKey, $comment="" );
	static function encodeIdentifier( $stringOrObject );
	static function getSiteName();
	static function getBrainstageUrl();

	function build();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Brainstage implements BrainstageInterface {


	const PLUGIN_DIR = 'classes/Brainstage/Plugins/';


	private $_Framework;
	private $_Request;
	private $_Template;
	private $_errors;
	private static $_Dictionary;
	private static $_plugins;
	private static $_initializedPlugins;
	private static $_pluginPrivileges;


	final public function __construct() {
		$this->_Framework = new \rsCore\Framework();
		$this->_Request = \rsCore\Core::core()->getRequestPath();
		$this->_errors = array();
		self::$_initializedPlugins = array();
		$this->initSession();
		self::initDictionary();
		$this->initPlugins();
		$this->_Template = $this->instantiateTemplate( $this->getRequestPath() );
		$this->init();
	}


	/** Initialisiert die User-Session
	 *
	 * @access protected
	 * @return void
	 */
	protected function initSession() {
		if( !\rsCore\Auth::isLoggedin() ) {
			try {
				if( isset( $_POST['username'], $_POST['password'] ) )
					\rsCore\Auth::login( $_POST['username'], $_POST['password'] );
			} catch( \Exception $Exception ) {
				$this->_errors[] = $Exception;
			}
		}
	}


	/** Initialisiert das Brainstage-spezifische Dictionary
	 *
	 * @access protected
	 * @return void
	 */
	protected static function initDictionary() {
		$Dictionary = \rsCore\Core::core()->getDictionary();
		$Language = $Dictionary ? $Dictionary->getLanguage() : null;
		self::$_Dictionary = new InternalDictionary( $Language );
	#	\rsCore\Core::core()->setDictionary( $this->_Dictionary );
	}


	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access protected
	 * @return void
	 */
	protected function initPlugins() {
		foreach( self::getPlugins() as $pluginName ) {
			if( isLoggedin() && user()->mayUseClass( $pluginName ) ) {
				try {
					self::registerPrivileges( $pluginName, $pluginName::registerPrivileges() );
					$pluginName::brainstageRegistration( $this->getFramework() );
					self::$_initializedPlugins[] = $pluginName;
				} catch( \Exception $Exception ) {
					\rsCore\ErrorHandler::catchException( $Exception );
				}
			}
		}
	}


	/** Lädt sämtliche Plugins
	 *
	 * @access public
	 * @return array
	 */
	public static function getPlugins() {
		if( !self::$_plugins )
			self::$_plugins = array_merge( self::loadBrainstagePlugins(), \Autoload::getPlugins() );
		return self::$_plugins;
	}


	/** Gibt die Namen der erfolgreich eingebundenen Plugins zurück
	 *
	 * @access public
	 * @return array
	 */
	public static function getInitializedPlugins() {
		return self::$_initializedPlugins;
	}


	/** Lädt interne Brainstage-Plugins
	 *
	 * @return array
	 */
	protected static function loadBrainstagePlugins() {
		$plugins = array();
		$namespace = '\\'. __NAMESPACE__ .'\\'. \Autoload::PLUGIN_NAMESPACE .'\\';
		foreach( scandir( self::PLUGIN_DIR ) as $fileName ) {
			$filePath = self::PLUGIN_DIR .'/'. $fileName;
			if( is_file( $filePath ) ) {
				$fileNameComponents = explode( '.', $fileName );
				if( array_pop( $fileNameComponents ) == 'php' ) {
					$pluginName = $namespace . $fileNameComponents[0];
					$Reflection = new \ReflectionClass( $pluginName );
					if( $Reflection->isSubclassOf( \Autoload::PLUGIN_INTERFACE ) ) {
						$sortValue = 0;
						if( $Reflection->isSubclassOf( $namespace . 'PluginInterface' ) ) {
							$sortValue = intval( forward_static_call( array($pluginName, 'brainstageSortValue') ) );
						}
						$plugins[ $sortValue ][] = $pluginName;
					}
				}
			}
		}
		krsort( $plugins );
		$orderedPlugins = array();
		foreach( $plugins as $pluginSet ) {
			foreach( $pluginSet as $plugin ) {
				$orderedPlugins[] = $plugin;
			}
		}
		return $orderedPlugins;
	}


	/** Instantiiert das dem Dokument zugewiesene Template
	 *
	 * @access protected
	 * @return object
	 */
	protected function instantiateTemplate( $requestPath ) {
		$baseTemplate = '\\'. __NAMESPACE__ .'\\'. \Autoload::TEMPLATE_NAMESPACE .'\\Base';
		$authTemplate = '\\'. __NAMESPACE__ .'\\'. \Autoload::TEMPLATE_NAMESPACE .'\\Auth';
		$templateName = current( $requestPath ) ? '\\'. __NAMESPACE__ .'\\'. \Autoload::TEMPLATE_NAMESPACE .'\\'. current( $requestPath ) : null;
		if( !\rsCore\Auth::isLoggedin() )
			return new $authTemplate( $this, $this->getRequest()->getRequestHandler(), null );
		elseif( $templateName != null && class_exists( $templateName, true ) )
			return new $templateName( $this, $this->getRequest()->getRequestHandler(), null );
		elseif( class_exists( $baseTemplate, true ) )
			return new $baseTemplate( $this, $this->getRequest()->getRequestHandler(), null );
		else
			throw new \rsCore\Exception( "Template '". $templateName ."' could not be found." );
		return null;
	}


	/** Verzeichnet die angemeldeten Rechtebezeichner eines Plugins
	 *
	 * @access public
	 * @return void
	 */
	public static function registerPrivileges( $pluginName, $privileges=null ) {
		if( self::$_pluginPrivileges === null )
			self::$_pluginPrivileges = array();
		if( is_string( $privileges ) )
			$privileges = explode( ',', $privileges );
		if( is_array( $privileges ) ) {
			foreach( $privileges as $key => $value ) {
				$type = $value;
				$privilege = $key;
				if( !is_string( $key ) ) {
					$type = 'boolean';
					$privilege = $value;
				}
				self::$_pluginPrivileges[ $pluginName ][ $privilege ] = $type;
			}
		}
		else
			self::$_pluginPrivileges[ $pluginName ] = 'boolean';
	}


	/** Gibt die von Plugins angeforderten Rechtebezeichnungen zurück
	 *
	 * @access public
	 * @return array
	 */
	public static function getPluginPrivileges() {
		return self::$_pluginPrivileges;
	}


	/** Gibt das Framework-Objekt zurück
	 *
	 * @access public
	 * @return \rsCore\Framework
	 */
	public function getFramework() {
		return $this->_Framework;
	}


	/** Gibt den RequestPath zurück
	 *
	 * @access public
	 * @return \rsCore\RequestPath
	 */
	public function getRequest() {
		return $this->_Request;
	}


	/** Gibt den bereinigten Anfrage-Pfad zurück
	 *
	 * @access protected
	 * @return array
	 */
	public function getRequestPath() {
		$Request = $this->getRequest();
		$dirComponents = explode( '/', dirname( BASE_SCRIPT_FILE ) );
		$lastPathComponent = array_pop( $dirComponents );
		$pathComponents = $Request->path;
		foreach( $pathComponents as $index => $pathComponent ) {
			unset( $pathComponents[ $index ] );
			if( $pathComponent == $lastPathComponent )
				break;
		}
		return $pathComponents;
	}


	/** Gibt das Template zurück
	 *
	 * @access public
	 * @return object
	 */
	public function getTemplate() {
		return $this->_Template;
	}


	/** Registriert einen Hook
	 *
	 * @access public
	 */
	public function registerHook( $Object, $event, $method=null ) {
		$this->getFramework()->registerHook( $Object, $event, $method );
	}


	/** Entfernt einen Hook
	 *
	 * @access public
	 */
	public function unregisterHook( $Object, $event ) {
		$this->getFramework()->unregisterHook( $Object, $event );
	}


	/** Ruft Hooks eines Events auf
	 *
	 * @access public
	 */
	public function callHooks( $event, $params ) {
		$this->getFramework()->callHooks( $event, $params, true );
	}


	/** Übersetzt einen String mithilfe des internen Brainstage-Wörterbuchs
	 *
	 * @param string $translationKey
	 * @access public
	 */
	public static function translate( $translationKey, $comment="" ) {
		if( !self::$_Dictionary )
			self::initDictionary();
		$Translation = self::$_Dictionary->get( $translationKey, $comment );
		if( $Translation )
			return $Translation->getTranslatedString();
		return $translationKey;
	}


	/** Kodiert einen Object-Identifier zur Verwendung als ID im DOM
	 *
	 * @param mixed $stringOrObject
	 * @access public
	 */
	public static function encodeIdentifier( $stringOrObject, $fullIdentifier=true ) {
		if( is_object( $stringOrObject ) )
			$identifier = \rsCore\Core::callMethod( $stringOrObject, 'getIdentifier', array( $fullIdentifier ) );
		elseif( is_string( $stringOrObject ) )
			$identifier = $stringOrObject;
		return str_replace( '/', '_', trim( str_replace( '\\', '/', $identifier ), '/' ) );
	}


	/** Gibt den Namen der Site zurück
	 *
	 * @return string
	 * @access public
	 */
	public static function getSiteName() {
		$SitenameSetting = \Brainstage\Setting::getMixedSetting( 'Brainstage/Sitename', true );
		return $SitenameSetting->value;
	}


	/** Setzt den Namen der Site
	 *
	 * @param string $name
	 * @access public
	 */
	public static function setSiteName( $name ) {
		$SitenameSetting = \Brainstage\Setting::getMixedSetting( 'Brainstage/Sitename', true );
		$SitenameSetting->value = trim( strval( $name ) );
		return $SitenameSetting->adopt();
	}


	/** Gibt die URL zu Brainstage zurück
	 *
	 * @return string
	 * @access public
	 */
	public static function getBrainstageUrl() {
		$domainBase = rsCore()->getRequestPath()->domain->domainbase;
		return $domainBase;
	}


	/** Dient als Konstruktor-Erweiterung
	 *
	 * @access protected
	 */
	protected function init() {
		$Template = $this->getTemplate();
		if( $Template instanceof \Brainstage\Templates\Auth )
			$Template->setErrors( $this->_errors );
	}


	/** Startet den Zusammenbau der Seite und gibt den Quelltext aus
	 *
	 * @access public
	 */
	public function build( $output=true ) {
		$source = $this->getTemplate()->build( $output );
		if( $output )
			echo $source;
		else
			return $source;
	}


}