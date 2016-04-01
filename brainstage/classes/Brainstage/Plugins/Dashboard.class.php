<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Plugins;

include_once( __DIR__ .'/Dashboard/Plugin.class.php' );


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface DashboardInterface extends PluginInterface {

	static function getPlugins();
	static function getPluginInterfaceName();

}


/** DashboardPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \Brainstage\Plugin
 */
class Dashboard extends \Brainstage\Plugin implements DashboardInterface {


	const PLUGIN_DIR = 'classes/Brainstage/Plugins/Dashboard/';
	const PLUGIN_NAMESPACE = 'Dashboard';
	const PLUGIN_INTERFACE = 'PluginInterface';


	private static $_DashboardFramework;
	private static $_plugins = array();

	private $_Footer;
	private $_Head;


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param Framework $Framework
	 */
	public static function brainstageRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'initTemplate', 'onTemplateInit' );
		$Framework->registerHook( $Plugin, 'getNavigatorItem' );
		$Framework->registerHook( $Plugin, 'buildHead' );
		$Framework->registerHook( $Plugin, 'buildBody' );
		$Framework->registerHook( $Plugin, 'beforeBuild' );
	}


	/** Wird von Brainstage aufgerufen, damit sich das Plugin in die Menüreihenfolge einsortieren kann
	 * @return int Desto höher der Wert, desto weiter oben erscheint das Plugin
	 */
	public static function brainstageSortValue() {
		return 98;
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$DashboardFramework = self::getDashboardFramework();
		foreach( $DashboardFramework->getHookedObjects() as $DashboardPlugin ) {
			$DashboardPlugin::apiRegistration( $Framework );
		}
	#	$Framework->registerHook( $Plugin, 'list', 'api_getDashboard' );
	}


	/** Gibt das Dashboard-Framework zurück
	 * @return Framework
	 * @final
	 */
	protected static function getDashboardFramework() {
		if( !self::$_DashboardFramework ) {
			self::$_DashboardFramework = new \rsCore\Framework();
			self::initPlugins();
		}
		return self::$_DashboardFramework;
	}


	/** Gibt das Dashboard-Framework zurück
	 * @return Framework
	 * @final
	 */
	public static function getPluginInterfaceName() {
		return '\\'. __NAMESPACE__ .'\\'. self::PLUGIN_NAMESPACE .'\\' . self::PLUGIN_INTERFACE;
	}


/* Private Methoden */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


	protected function getPluginTitle() {
		return self::t("Dashboard");
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
					$widgetTitle = $pluginName::getDashboardWidgetTitle();
				} catch( \Exception $Exception ) {
					\rsCore\ErrorHandler::catchException( $Exception );
				}
				$plugins[ $widgetTitle ] = $pluginName;
			}
		}
		ksort( $plugins );
		self::$_plugins = array_values( $plugins );
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return $this->getPluginTitle();
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$this->_Head = $Head;

		$Head->linkScript( 'static/js/dashboard.js' );
		$Head->linkStylesheet( 'static/css/Dashboard.css' );
		self::getDashboardFramework()->callHooks( 'buildHead', $Head );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$PluginsRow = self::buildSection( $Container, self::t("Plugins") );
		$NativeRow = self::buildSection( $Container, self::t("General") );

		foreach( self::getDashboardFramework()->getHooks( 'buildWidget' ) as $Hook ) {
			$hookedObject = $Hook->getObject();

			if( strpos( $hookedObject->getIdentifier(), str_replace( '\\', '/', __NAMESPACE__ .'/'. self::PLUGIN_NAMESPACE ) ) !== false )
				$Row = $NativeRow;
			else
				$Row = $PluginsRow;

			$identifier = \Brainstage\Brainstage::encodeIdentifier( $hookedObject );
			$widgetTitle = $hookedObject->getIdentifier();
			
			try {
				$widgetTitle = $hookedObject::getDashboardWidgetTitle();
				$View = new \rsCore\Container( 'div.view' );
				$Hook->call( $View );
			} catch( \Exception $Exception ) {
				\rsCore\ErrorHandler::catchException( $Exception );
			}

			$attr = array(
				'id' => $identifier .'_DashboardWidget',
				'data-identifier' => $hookedObject->getIdentifier()
			);
			$WidgetContainer = $Row->subordinate( 'div.col-sm-12.col-md-6.col-lg-4 > div.widget', $attr );
			$WidgetContainer->subordinate( 'h2', $widgetTitle )->subordinate( 'span.icon.icon-right-open' );
			$WidgetContainer->swallow( $View );
		}

		$this->_Footer = $Container->subordinate( 'div.footline' );
	}


	/** Wird unmittelbar vor der build()-Operation aufgerufen
	 */
	public function beforeBuild() {
	#	$this->_Head;
		if( $this->_Footer )
			$this->_Footer->subordinate( 'span.runtime > small', t("Execution duration") .': '. \rsCore\Core::core()->getRuntimeDuration() .'s' );
	}


	/** Fügt eine Sektion mitsamt Überschrift ein
	 * @param \rsCore\Container $Container
	 * @param string $headline
	 * @return \rsCore\Container
	 */
	protected static function buildSection( \rsCore\Container $Container, $headline ) {
		$Section = $Container->subordinate( 'div.section' );
		$Section->subordinate( 'h1', $headline );
		return $Section->subordinate( 'div.row.clearfix' );
	}


/* API Plugin */

	/** Gibt ein Array aller bekannten Sprachen aus
	 * @return array
	 */
	public function api_getDashboard( $params ) {
		self::throwExceptionIfNotAuthorized();

		$Dashboard = array();
		foreach( \Brainstage\Language::getDashboard() as $Language ) {
			$columns = $Language->getColumns();
			unset( $columns['id'] );
			$Dashboard[] = $columns;
		}
		return $Dashboard;
	}


	/** Fügt eine Sprache hinzu
	 * @return boolean
	 */
	public function api_addLanguage( $params ) {
		self::throwExceptionIfNotPrivileged( 'add' );

		$locale = valueByKey( $params, 'locale' );
		$shortCode = valueByKey( $params, 'shortCode', \rsCore\Localization::extractLanguageCode( $locale ) );
		$name = valueByKey( $params, 'name' );

		if( !$name || !$locale || strlen($name) <= 0 || strlen($locale) <= 0 )
			return false;

		$Language = \Brainstage\Language::addLanguage( $name, $shortCode, $locale );
		if( $Language )
			return true;
		return false;
	}


/* Dashboard Framework */

	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access protected
	 * @return void
	 */
	protected static function initPlugins() {
		foreach( self::getPlugins() as $pluginName ) {
		#	if( isLoggedin() && user()->mayUseClass( $pluginName ) ) {
			try {
			//	self::registerPrivileges( $pluginName, $pluginName::registerPrivileges() );
				$pluginName::dashboardRegistration( self::getDashboardFramework() );
			} catch( \Exception $Exception ) {
				\rsCore\ErrorHandler::catchException( $Exception );
			}
		}
	}


	/** Lädt sämtliche Plugins
	 *
	 * @access public
	 * @return array
	 */
	public static function getPlugins() {
		return array_merge( self::$_plugins, self::loadDashboardPlugins() );
	}


	/** Lädt interne Brainstage-Plugins
	 *
	 * @return array
	 */
	protected static function loadDashboardPlugins() {
		$plugins = array();
		$namespace = '\\'. __NAMESPACE__ .'\\'. self::PLUGIN_NAMESPACE .'\\';
		foreach( scandir( self::PLUGIN_DIR ) as $fileName ) {
			$filePath = self::PLUGIN_DIR .'/'. $fileName;
			if( is_file( $filePath ) ) {
				$fileNameComponents = explode( '.', $fileName );
				if( array_pop( $fileNameComponents ) == 'php' ) {
					$pluginName = $namespace . $fileNameComponents[0];
					$Reflection = new \ReflectionClass( $pluginName );
					if( $Reflection->isSubclassOf( self::getPluginInterfaceName() ) ) {
						$title = forward_static_call( array($pluginName, 'getDashboardWidgetTitle') );
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


}