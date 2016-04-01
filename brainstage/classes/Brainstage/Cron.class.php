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
interface CronInterface {

	function register( \rsCore\Plugin $Plugin, $method, $frequency=10 );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Cron extends \rsCore\CoreClass implements CronInterface {
	
	
	const PLUGIN_INTERFACE = 'CronPluginInterface';


	private $_Framework;
	private static $_initializedPlugins;
	private static $_initializedCronjobs = array();


/* Framework methods */

	/** Gibt das Framework zurück
	 * @return Framework
	 * @final
	 */
	final protected function getFramework() {
		return $this->_Framework;
	}


	/** Ruft im Framework die Hooks auf
	 * @final
	 * @return void
	 */
	final protected function callHooks() {
		$results = array();
		if( $this->getFramework() ) {
			$hooks = array();
			$now = time();
			foreach( $this->getFramework()->getHooks() as $events ) {
				foreach( $events as $Hook )
					$hooks[] = $Hook;
			}
			foreach( $hooks as $Hook ) {
				$HookedObject = $Hook->getObject();
				$method = $Hook->getMethod();
				$frequency = $Hook->getEvent();
				$pluginIdentifier = $HookedObject->getIdentifier();
				$actionIdentifier = $pluginIdentifier .':'. $method;
				
				self::$_initializedCronjobs[ $pluginIdentifier ][ $method ] = $frequency;
				
				$CronRun = CronRun::getByHook( $Hook );
				$secondsSinceLastExecution = $now - $CronRun->lastExecution;
				$shouldBeExecuted = $secondsSinceLastExecution > $frequency;
				
				if( $shouldBeExecuted ) {
					try {
						$results[ $actionIdentifier ] = $Hook->call();
						$CronRun->lastExecution = $now;
					} catch( \Exception $Exception ) {
						\rsCore\ErrorHandler::catchException( $Exception );
					}
				}
			}
		}
		return $results;
	}


/* General */

	/** Konstruktor
	 * @final
	 */
	final public function __construct() {
		set_time_limit(90);
		$this->_Framework = new \rsCore\Framework();
		$this->initPlugins();
		$this->init();
		$this->execute();
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access protected
	 * @return void
	 */
	protected function initPlugins() {
		if( !self::$_initializedPlugins ) {
			self::$_initializedPlugins = array();
			$pluginInterfaceName = __NAMESPACE__ .'\\'. self::PLUGIN_INTERFACE;
			foreach( Brainstage::getPlugins() as $pluginName ) {
				try {
					$Reflection = new \ReflectionClass( $pluginName );
					if( $Reflection->isSubclassOf( $pluginInterfaceName ) ) {
						$pluginName::cronRegistration( $this );
						self::$_initializedPlugins[] = $pluginName;
					}
				} catch( \Exception $Exception ) {
					\rsCore\ErrorHandler::catchException( $Exception );
				}
			}
		}
	}


	/** Führt die Cronjobs aus und gibt eventuelle Ergebnisse als JSON aus
	 *
	 * @access protected
	 * @param boolean $die
	 * @return array
	 */
	protected function execute( $die=true ) {
		$results = $this->callHooks();
		$output = array(
			'results' => $results,
			'cronjobs' => self::$_initializedCronjobs
		);
		$json = json_encode( $output );
		
		if( $die )
			die( $json );
		echo $json;
		return $results;
	}


/* Public methods */

	/** Registriert ein Plugin für den Cron
	 *
	 * @return void
	 */
	public function register( \rsCore\Plugin $Plugin, $method, $frequency=10 ) {
		if( is_int( $frequency ) )
			$frequencyInSeconds = $frequency;
		elseif( is_string( $frequency ) ) {
			$integerPart = intval( $frequency );
			$unit = strtolower( substr( $frequency, strlen($integerPart), 1 ) );
			if( $unit == 's' )
				$frequencyInSeconds = $integerPart;
			elseif( $unit == 'm' )
				$frequencyInSeconds = $integerPart * 60;
			elseif( $unit == 'h' )
				$frequencyInSeconds = $integerPart * 3600;
			elseif( $unit == 'd' )
				$frequencyInSeconds = $integerPart * 86400;
		}
		if( $frequencyInSeconds )
			$this->getFramework()->registerHook( $Plugin, $frequencyInSeconds, $method );
	}


}