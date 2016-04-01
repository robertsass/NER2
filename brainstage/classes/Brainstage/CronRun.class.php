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
interface CronRunInterface {

	static function getByHook( \rsCore\FrameworkHook $Hook, $createIfDoesNotExist=true );

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CronRun extends \rsCore\DatabaseDatasetAbstract implements CronRunInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'brainstage-cron-runs';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Findet einen Datensatz anhand eines Hooks
	 * @param \rsCore\FrameworkHook $Hook
	 * @param boolean $createIfDoesNotExist
	 * @return Setting
	 * @api
	 */
	public static function getByHook( \rsCore\FrameworkHook $Hook, $createIfDoesNotExist=true ) {
		$HookedObject = $Hook->getObject();
		$pluginIdentifier = $HookedObject->getIdentifier();
		$method = $Hook->getMethod();
		
		$Dataset = self::getByColumns( array('plugin' => $pluginIdentifier, 'method' => $method), false );
		if( !$Dataset ) {
			$Dataset = self::create();
			if( $Dataset ) {
				$Dataset->plugin = $pluginIdentifier;
				$Dataset->method = $method;
				$Dataset->lastExecution = time();
				$Dataset->adopt();
			}
		}
		return $Dataset;
	}


/* Protected methods */
	
	protected function encodeLastExecution( $value ) {
		return intval( $value );
	}
	
	
	protected function decodeLastExecution( $value ) {
		return $value;
	}


}