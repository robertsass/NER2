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
interface SettingInterface {

	static function getBooleanSetting( $key, $createIfDoesNotExist=false );
	static function getMixedSetting( $key, $createIfDoesNotExist=false );
	static function getTextSetting( $key, $createIfDoesNotExist=false );

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Setting extends \rsCore\DatabaseDatasetAbstract implements SettingInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
	#	\rsCore\Core::core()->registerDatabaseDatasetHandler( \rsCore\Core::core()->database( 'brainstage-settings-boolean' ), '\\'. __CLASS__ );
	#	\rsCore\Core::core()->registerDatabaseDatasetHandler( \rsCore\Core::core()->database( 'brainstage-settings-mixed' ), '\\'. __CLASS__ );
	#	\rsCore\Core::core()->registerDatabaseDatasetHandler( \rsCore\Core::core()->database( 'brainstage-settings-text' ), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Findet einen Setting beim Schlüssel oder erzeugt einen neuen Setting
	 * @param string $key
	 * @param boolean $createIfDoesNotExist
	 * @return Setting
	 * @api
	 */
	public static function getBooleanSetting( $key, $createIfDoesNotExist=false ) {
		return SettingBoolean::getSetting( $key, $createIfDoesNotExist );
	}


	/** Findet einen Setting beim Schlüssel oder erzeugt einen neuen Setting
	 * @param string $key
	 * @param boolean $createIfDoesNotExist
	 * @return Setting
	 * @api
	 */
	public static function getMixedSetting( $key, $createIfDoesNotExist=false ) {
		return SettingMixed::getSetting( $key, $createIfDoesNotExist );
	}


	/** Findet einen Setting beim Schlüssel oder erzeugt einen neuen Setting
	 * @param string $key
	 * @param boolean $createIfDoesNotExist
	 * @return Setting
	 * @api
	 */
	public static function getTextSetting( $key, $createIfDoesNotExist=false ) {
		return SettingText::getSetting( $key, $createIfDoesNotExist );
	}


	/** Findet einen Setting beim Schlüssel oder erzeugt einen neuen Setting
	 * @param string $key
	 * @param boolean $createIfDoesNotExist
	 * @return SettingText
	 * @api
	 */
	public static function getSetting( $key, $createIfDoesNotExist=false ) {
		$key = trim( $key );
		$Setting = static::getByColumns( array('key' => $key) );
		if( !$Setting && $createIfDoesNotExist ) {
			$Setting = static::create();
			if( $Setting ) {
				$Setting->key = $key;
				$Setting->adopt();
			}
		}
		return $Setting;
	}


	/** Entfernt den SettingText
	 * @return Text
	 * @api
	 */
	public function remove() {
		return parent::remove();
	}


}