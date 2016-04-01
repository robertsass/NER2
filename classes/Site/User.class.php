<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Site;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface UserInterface {

	public static function addUser( $email );

	public static function getUserByEmail( $email );

	public function getBrainstageUser();
	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class User extends \rsCore\DatabaseDatasetAbstract implements UserInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'users';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt einen neuen Nutzer an
	 * @param string $email
	 * @return User
	 * @api
	 */
	public static function addUser( $email ) {
		if( self::getUserByEmail( $email ) )
			throw new \Exception( "E-Mail already registered." );

		$User = self::create();
		if( $User ) {
			$BrainstageUser = \Brainstage\User::addUser( $email );
			if( $BrainstageUser ) {
				$BrainstageUser->platformUserId = $User->getPrimaryKeyValue();
				$BrainstageUser->adopt();
			}
		}
		return $User;
	}


	/** Gibt einen Nutzer zurück
	 * @param string $email
	 * @return User
	 * @api
	 */
	public static function getUserByEmail( $email ) {
		$BrainstageUser = \Brainstage\User::getUserByEmail( $email );
		if( $BrainstageUser && intval( $BrainstageUser->platformUserId ) )
			return self::getByPrimaryKey( $BrainstageUser->platformUserId );
		return null;
	}


/* Public methods */

	/** Gibt den Brainstage-Nutzer zurück
	 * @return \Brainstage\User
	 * @api
	 */
	public function getBrainstageUser() {
		return \Brainstage\User::getUserByPlatformUser( $this );
	}


	/** Löscht den Platform-Nutzer
	 * @param boolean $deleteBrainstageUserToo
	 * @return boolean
	 * @api
	 */
	public function remove( $deleteBrainstageUserToo=true ) {
		$BrainstageUser = $this->getBrainstageUser();
		if( $BrainstageUser ) {
			if( $deleteBrainstageUserToo ) {
				if( $BrainstageUser->remove() )
					return parent::remove();
			}
			else {
				$BrainstageUser->platformUserId = null;
				$BrainstageUser->adopt();
				return parent::remove();
			}
		}
		return false;
	}


}