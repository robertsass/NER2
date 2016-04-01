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
interface UserInterface {

	static function addUser( $email, $password, $throwExceptions );

	static function getUserById( $userId );
	static function getUserByEmail( $email );
	static function getUserByPlatformUser( $userInstanceOrId );
	static function getUsersByName( $name );
	static function getUsers( $limit, $start );

	static function crypt( $value, $salt );

	function may( $key, $comparisonValue );
	function mayUseClass( $className );
	function getRight( $key );
	function getGroupsRight( $key );
	function getRights();
	function getGroupsRights();
	function getPluginSpecificRight( $pluginName, $key, $includeGroupRights );

	function getGroups();

	function verifyPassword( $password );
	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class User extends \rsCore\DatabaseDatasetAbstract implements UserInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-users';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Erzeugt einen neuen User
	 * @param string $email
	 * @param string $password
	 * @return User
	 * @api
	 */
	public static function addUser( $email, $password=null, $throwExceptions=true ) {
		if( static::getUserByEmail( $email ) ) {
			if( $throwExceptions )
				throw new \rsCore\Exception( "This email address is already registered by another user." );
			return null;
		}
		else {
			$User = self::create();
			if( $User ) {
				$User->email = $email;
				if( $password !== null )
					$User->password = $password;
				$User->adopt();
			}
			return $User;
		}
	}


	/** Findet einen User anhand seiner ID
	 * @param integer $userId
	 * @return User
	 * @api
	 */
	public static function getUserById( $userId ) {
		return self::getByPrimaryKey( intval( $userId ) );
	}


	/** Findet einen User anhand der eMail-Adresse
	 * @param string $email
	 * @return User
	 * @api
	 */
	public static function getUserByEmail( $email ) {
		return self::getByColumn( 'email', $email );
	}


	/** Findet einen User zu einem Platform-User
	 * @param mixed $userInstanceOrId
	 * @return User
	 * @api
	 */
	public static function getUserByPlatformUser( $userInstanceOrId ) {
		if( is_object( $userInstanceOrId ) && $userInstanceOrId instanceof \rsCore\DatabaseDataset )
			$userId = $userInstanceOrId->getPrimaryKeyValue();
		else
			$userId = intval( $userInstanceOrId );
		return self::getByColumn( 'platformUserId', $userId );
	}


	/** Findet Users anhand des Namens
	 * @param string $name
	 * @return array Array von User-Instanzen
	 * @api
	 */
	public static function getUsersByName( $name ) {
		return self::getByColumn( 'name', $name, true );
	}


	/** Holt alle Users
	 * @param integer $limit
	 * @param integer $start
	 * @return array Array von User-Instanzen
	 * @api
	 */
	public static function getUsers( $limit=null, $start=0 ) {
		return self::getDatabaseConnection()->getAll( null, 'ORDER BY `id` DESC ' .($limit !== null ? 'LIMIT '. intval($start) .','. intval($limit) : '') );
	}


	/** Hashing eines Wertes
	 * @param string $value
	 * @param string $salt
	 * @return string
	 * @api
	 */
	public static function crypt( $value, $salt=null ) {
		if( !$salt )
			if( defined('CRYPT_SALT') )
				$salt = CRYPT_SALT;
			else
				$salt = '';
		$saltedInput = $value . $salt;
		$n = 0;
		$crc32 = crc32( $saltedInput );
		foreach( str_split( substr( $crc32, 2, 2 ), 1 ) as $c )
			$n += intval( $c );
		$sha = str_split( sha1( $saltedInput ), $n );
		$md5 = str_split( md5( $saltedInput ), $n );
		$hash = '';
		foreach( $sha as $index => $shaPart ) {
			$md5Part = array_key_exists( $index, $md5 ) ? $md5[ $index ] : '';
			$hash .= $shaPart . $md5Part;
		}
		return $hash;
	}


/* Public methods */

	/** Prüft einen Rechtebezeichner auf Existenz oder einen bestimmten Wert
	 * @param string $key Rechtebezeichner
	 * @param mixed $comparisonValue Vergleichswert
	 * @return boolean
	 * @api
	 */
	public function may( $key, $comparisonValue=true, $includeGroupRights=false ) {
		if( $this->isSuperAdmin() )
			return true;

		// Replace Backslashes in Namespace Path
		$key = ltrim( $key, '\\' );
		$key = str_replace( '\\', '/', $key );

		$Right = $this->getRight( $key );
		if( $Right )
			return $Right->value == $comparisonValue;
		if( $includeGroupRights ) {
			foreach( $this->getGroupsRight( $key ) as $Right )
				if( $Right->value == $comparisonValue )
					return true;
		}
		return !$comparisonValue;
	}


	/** Prüft ob der Nutzer eine Klasse nutzen darf
	 * @param string $className Klassenbezeichnung samt Namespace
	 * @return boolean
	 * @api
	 */
	public function mayUseClass( $className ) {
		return $this->isSuperAdmin() || $this->may( $className, true, true );
	}


	/** Prüft ob der Nutzer das Superadmin-Recht hat
	 * @param boolean $includeGroup
	 * @return boolean
	 * @api
	 */
	public function isSuperAdmin( $includeGroup=true ) {
		$Right = $this->getRight( 'Brainstage:superadmin' );
		if( $Right && $Right->value )
			return true;
		if( $includeGroup ) {
			foreach( $this->getGroups() as $Group ) {
				if( $Group->isSuperAdmin() )
					return true;
			}
		}
		return false;
	}


	/** Gibt zu dem gegebenen Rechtebezeichner eine Instanz von UserRight zurück
	 * @param string $key Rechtebezeichner
	 * @param boolean $includeGroupRights
	 * @return UserRight
	 * @api
	 */
	public function getRight( $key, $includeGroupRights=false ) {
		$UserRight = UserRight::getRightByKey( $key, $this );
		if( !$includeGroupRights )
			return $UserRight;

		$rights = array( $UserRight );
		foreach( $this->getGroups() as $Group ) {
			$GroupRight = $Group->getRight( $key );
			if( $GroupRight )
				$rights[] = $GroupRight;
		}
		return $rights;
	}


	/** Sucht für alle Gruppen des Nutzers zu dem gegebenen Rechtebezeichner eine Instanz von UserRight zurück
	 * @param string $key Rechtebezeichner
	 * @return array Array von GroupRight-Instanzen
	 * @api
	 */
	public function getGroupsRight( $key ) {
		$rights = array();
		foreach( $this->getGroups() as $Group ) {
			$GroupRight = $Group->getRight( $key );
			if( $GroupRight )
				$rights[] = $GroupRight;
		}
		return $rights;
	}


	/** Gibt die Rechte des Nutzers zurück
	 * @param boolean $includeGroupRights
	 * @return array Array von UserRight-Instanzen
	 * @api
	 */
	public function getRights( $includeGroupRights=false ) {
		$UserRights = UserRight::getRightsByUser( $this );
		if( $includeGroupRights === false )
			return $UserRights;

		$rights = array( $UserRights );
		foreach( $this->getGroups() as $Group ) {
			$GroupRights = $Group->getRights();
			if( $GroupRights )
				$rights[] = $GroupRights;
		}
		return $rights;
	}


	/** Gibt für jede Gruppe des Nutzers die Rechte zurück
	 * @return array Array von GroupRight-Instanzen
	 * @api
	 */
	public function getGroupsRights() {
		$rights = array();
		foreach( $this->getGroups() as $Group ) {
			$GroupRights = $Group->getRights();
			if( $GroupRights )
				$rights[] = $GroupRights;
		}
		return $rights;
	}


	/** Gibt zu dem gegebenen Plugin und Rechtebezeichner eine Instanz von UserRight zurück
	 * @param string $pluginName Pluginname
	 * @param string $key Rechtebezeichner
	 * @param boolean $includeGroupRights
	 * @return UserRight
	 * @api
	 */
	public function getPluginSpecificRight( $pluginName, $key, $includeGroupRights=false ) {
		// Replace Backslashes in Namespace Path
		$pluginName = ltrim( $pluginName, '\\' );
		$pluginName = str_replace( '\\', '/', $pluginName );

		$key = $pluginName .':'. $key;
		$UserRight = UserRight::getRightByKey( $key, $this );
		if( !$includeGroupRights )
			return $UserRight;

		$rights = array();
		if( $UserRight )
			$rights[] = $UserRight;
		foreach( $this->getGroups() as $Group ) {
			$GroupRight = $Group->getRight( $key );
			if( $GroupRight )
				$rights[] = $GroupRight;
		}
		return $rights;
	}


	/** Gibt die Rechte des Nutzers zurück
	 * @return array Array von UserRight-Instanzen
	 * @api
	 */
	public function getGroups() {
		return UserGroup::getGroupsByUser( $this );
	}


	/** Prüft das Passwort des Users mit dem übergebenen
	 * @param string $password
	 * @return boolean
	 * @api
	 */
	public function verifyPassword( $password ) {
		return static::crypt( $password ) == $this->password;
	}


	/** Entfernt den User
	 * @return boolean
	 * @api
	 * @todo Ggf. den Platform-User mitlöschen, mindestens an das Platform-User-Objekt eine Notification über die Löschung senden
	 */
	public function remove() {
		foreach( UserGroup::getRelationsByUser( $this ) as $Relation )
			$Relation->removeRelation();
		foreach( self::getRights() as $Right )
			$Right->remove();
		return parent::remove();
	}


/* Protected methods */

	/** Bildet beim Setzen des Passwortes dessen Hash
	 * @param string $value
	 * @return string
	 * @internal
	 */
	protected function encodePassword( $value ) {
		return static::crypt( $value );
	}


	protected function decodeLastLogin( $value ) {
		return \rsCore\DatabaseConnector::decodeDatetime( $value );
	}


	protected function encodeLastLogin( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


}