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
interface SettingMixedInterface {

	static function getSetting( $key, $createIfDoesNotExist=false );

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class SettingMixed extends Setting implements SettingMixedInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-settings-mixed';


	/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


}