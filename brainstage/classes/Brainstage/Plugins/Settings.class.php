<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Plugins;

include_once( __DIR__ .'/Settings/Plugin.class.php' );


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface SettingsInterface extends PluginInterface {

	static function getPlugins();
	static function getPluginInterfaceName();

}


/** SettingsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Settings extends \Brainstage\Plugin implements SettingsInterface {


	const PLUGIN_DIR = 'classes/Brainstage/Plugins/Settings/';
	const PLUGIN_NAMESPACE = 'Settings';
	const PLUGIN_INTERFACE = 'PluginInterface';


	private static $_SettingsFramework;
	private static $_plugins = array();
// 	private static $_pluginPrivileges;


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function brainstageRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance( $Framework );
		$Framework->registerHook( $Plugin, 'initTemplate', 'onTemplateInit' );
		$Framework->registerHook( $Plugin, 'getNavigatorItem' );
		$Framework->registerHook( $Plugin, 'buildHead' );
		$Framework->registerHook( $Plugin, 'buildBody' );
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$SettingsFramework = self::getSettingsFramework();
		foreach( $SettingsFramework->getHookedObjects() as $SettingsPlugin ) {
			$SettingsPlugin::apiRegistration( $Framework );
		}
	#	$Framework->registerHook( $Plugin, 'create', 'api_createUser' );
	#	@todo Init + iterate site plugins, let them register to the API
		/*
			foreach( self::getPlugins() as $pluginName ) {
				try {
					$pluginName::apiRegistration( $Framework );
				} catch( \Exception $Exception ) {
					\rsCore\ErrorHandler::catchException( $Exception );
				}
			}
		*/
	}


	/** Wird von Brainstage aufgerufen, damit sich das Plugin in die Menüreihenfolge einsortieren kann
	 * @return int Desto höher der Wert, desto weiter oben erscheint das Plugin
	 */
	public static function brainstageSortValue() {
		return 50;
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
	}


	/** Gibt das Settings-Framework zurück
	 * @return Framework
	 * @final
	 */
	protected static function getSettingsFramework() {
		if( !self::$_SettingsFramework ) {
			self::$_SettingsFramework = new \rsCore\Framework();
			self::initPlugins();
		}
		return self::$_SettingsFramework;
	}


	/** Gibt das Dashboard-Framework zurück
	 * @return Framework
	 * @final
	 */
	public static function getPluginInterfaceName() {
		return '\\'. __NAMESPACE__ .'\\'. self::PLUGIN_NAMESPACE .'\\' . self::PLUGIN_INTERFACE;
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


/* Brainstage Plugin */

	/** Wird von Brainstage ausgeführt, sobald das Template initialisiert wurde
	 */
	public function onTemplateInit() {
		$plugins = array();
		foreach( \Brainstage\Brainstage::getInitializedPlugins() as $pluginName ) {
			$Reflection = new \ReflectionClass( $pluginName );
			if( $Reflection->isSubclassOf( self::getPluginInterfaceName() ) ) {
				try {
					$title = $pluginName::getSettingsTitle();
				} catch( \Exception $Exception ) {
					\rsCore\ErrorHandler::catchException( $Exception );
				}
				$plugins[ $title ] = $pluginName;
			}
		}
		ksort( $plugins );
		self::$_plugins = array_values( $plugins );
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Settings");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		self::getSettingsFramework()->callHooks( 'buildHead', $Head );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'colset' );
		$this->buildTabBar( $Container );
		$this->buildTabViews( $Container );
	}


	/** Baut die Tabbar zusammen
	 * @param \rsCore\Container $Container
	 */
	public function buildTabBar( \rsCore\Container $Container ) {
		$tabAttr = array('role' => 'tab', 'data-toggle' => 'tab');
		$Bar = $Container->subordinate( 'header > ul.nav.nav-tabs' );

		foreach( self::getSettingsFramework()->getHooks( 'buildBody' ) as $Hook ) {
			$identifier = \Brainstage\Brainstage::encodeIdentifier( $Hook->getObject() );
			$title = \rsCore\Core::callMethod( $Hook->getObject(), 'getSettingsTitle' );

			$attr = array_merge( $tabAttr, array('data-target' => '#'. $identifier .'View') );
			$Bar->subordinate( 'li > a', $attr, $title );
		}
	}


	/** Baut die den Tabs zugehörigen Views
	 * @param \rsCore\Container $Container
	 */
	public function buildTabViews( \rsCore\Container $Container ) {
		$Container = $Container->subordinate( 'div.headered.tab-content' );

	#	var_dump( $this->getFramework() );
	#	var_dump( $this->_SettingsFramework, $this->_settingPlugins ); exit;

		foreach( self::getSettingsFramework()->getHooks( 'buildBody' ) as $Hook ) {
			$identifier = \Brainstage\Brainstage::encodeIdentifier( $Hook->getObject() );

			$attr = array(
				'id' => $identifier .'View',
				'data-identifier' => $Hook->getObject()->getIdentifier()
			);
			$Hook->call( $Container->subordinate( 'div.tab-pane', $attr ) );
		}
	}


/* Settings Framework */

	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access protected
	 * @return void
	 */
	protected static function initPlugins() {
		foreach( self::getPlugins() as $pluginName ) {
			if( isLoggedin() && user()->mayUseClass( $pluginName ) ) {
				try {
	//				self::registerPrivileges( $pluginName, $pluginName::registerPrivileges() );
					$pluginName::settingsRegistration( self::getSettingsFramework() );
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
		return array_merge( self::loadSettingsPlugins(), self::$_plugins );
	}


	/** Lädt interne Brainstage-Plugins
	 *
	 * @return array
	 */
	protected static function loadSettingsPlugins() {
		$plugins = array();
		$namespace = '\\'. __NAMESPACE__ .'\\'. self::PLUGIN_NAMESPACE .'\\';
		foreach( scandir( self::PLUGIN_DIR ) as $fileName ) {
			$filePath = self::PLUGIN_DIR .'/'. $fileName;
			if( is_file( $filePath ) ) {
				$fileNameComponents = explode( '.', $fileName );
				if( array_pop( $fileNameComponents ) == 'php' ) {
					$pluginName = $namespace . $fileNameComponents[0];
					$Reflection = new \ReflectionClass( $pluginName );
					if( $Reflection->isSubclassOf( $namespace . self::PLUGIN_INTERFACE ) ) {
						$title = forward_static_call( array($pluginName, 'getSettingsTitle') );
						$plugins[ $title ][] = $pluginName;
					}
				}
			}
		}
		ksort( $plugins );
		$orderedPlugins = array();
		foreach( $plugins as $pluginSet ) {
			foreach( $pluginSet as $plugin ) {
				$orderedPlugins[] = $plugin;
			}
		}
		return $orderedPlugins;
	}


	/** Verzeichnet die angemeldeten Rechtebezeichner eines Plugins
	 *
	 * @access public
	 * @return void
	 */
/*
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
*/


	/** Gibt die von Plugins angeforderten Rechtebezeichnungen zurück
	 *
	 * @access public
	 * @return array
	 */
/*
	public static function getPluginPrivileges() {
		return self::$_pluginPrivileges;
	}
*/


	/** Registriert einen Hook
	 *
	 * @access public
	 */
/*
	public function registerHook( $Object, $event, $method=null ) {
		$this->_SettingsFramework->registerHook( $Object, $event, $method );
	}
*/


	/** Entfernt einen Hook
	 *
	 * @access public
	 */
/*
	public function unregisterHook( $Object, $event ) {
		$this->_SettingsFramework->unregisterHook( $Object, $event );
	}
*/


}