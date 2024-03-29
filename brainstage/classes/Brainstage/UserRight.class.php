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
interface UserRightInterface {

	static function addRight( $key, $value, $userInstanceOrId, $throwExceptions );
	static function getRightById( $datasetId );
	static function getRightByKey( $key, $userInstanceOrId, $createIfDoesNotExist );
	static function getRightsByUser( $userInstanceOrId );

	function getJson();
	function getList();
	function inList( $needle );
	function check( $comparisonValue );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class UserRight extends Right implements UserRightInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-user-rights';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( self::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Vermerkt ein Recht für einen Nutzer
	 * @param string $key Rechtebezeichner
	 * @param string $value
	 * @param mixed $userInstanceOrId
	 * @param boolean $throwExceptions
	 * @return UserRight
	 * @api
	 */
	public static function addRight( $key, $value, $userInstanceOrId, $throwExceptions=true ) {
		$User = self::getUserByParameter( $userInstanceOrId );
		if( !$User )
			return null;
		if( static::getRightByKey( $key, $User ) ) {
			if( $throwExceptions )
				throw new \rsCore\Exception( "This right is already defined for this user." );
			return null;
		}
		else {
			$UserRight = self::create();
			if( $UserRight ) {
				$UserRight->key = $key;
				$UserRight->value = $value;
				$UserRight->userId = $User->getPrimaryKeyValue();
				$UserRight->adopt();
			}
			return $UserRight;
		}
	}


	/** Findet für einen User eine UserRight-Instanz anhand des Rechtebezeichners
	 * @param string $key
	 * @param mixed $userInstanceOrId
	 * @param boolean $createIfDoesNotExist
	 * @return UserRight
	 * @api
	 */
	public static function getRightByKey( $key, $userInstanceOrId, $createIfDoesNotExist=false ) {
		$User = self::getUserByParameter( $userInstanceOrId );
		if( !$User )
			return null;
		$UserRight = self::getByColumns( array(
			'userId' => $User->getPrimaryKeyValue(),
			'key' => $key
		) );
		if( !$UserRight && $createIfDoesNotExist )
			return static::addRight( $key, null, $User, false );
		return $UserRight;
	}


	/** Findet UserRights anhand des Users
	 * @param mixed $userInstanceOrId
	 * @return array Array von UserRight-Instanzen
	 * @api
	 */
	public static function getRightsByUser( $userInstanceOrId ) {
		$User = self::getUserByParameter( $userInstanceOrId );
		if( !$User )
			return null;
		return self::getByColumn( 'userId', intval( $User->getPrimaryKeyValue() ), true );
	}


}