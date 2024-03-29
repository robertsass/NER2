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
interface UserGroupInterface {

	static function addRelation( $userInstanceOrId, $groupInstanceOrId, $throwExceptions );
	static function getRelation( $userInstanceOrId, $groupInstanceOrId );
	static function getRelationById( $datasetId );
	static function getRelationsByUser( $userInstanceOrId );
	static function getRelationsByGroup( $groupInstanceOrId );
	static function getGroupsByUser( $userInstanceOrId );
	static function getUsersByGroup( $groupInstanceOrId );

	function getUser();
	function getGroup();
	function removeRelation();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class UserGroup extends \rsCore\DatabaseDatasetAbstract implements UserGroupInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-user-groups';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Erstellt eine Nutzer-Gruppe-Bindung
	 * @param mixed $userInstanceOrId
	 * @param mixed $groupInstanceOrId
	 * @param boolean $throwExceptions
	 * @return UserGroup
	 * @api
	 */
	public static function addRelation( $userInstanceOrId, $groupInstanceOrId, $throwExceptions=true ) {
		$User = self::getUserByParameter( $userInstanceOrId );
		if( !$User )
			return null;
		$Group = self::getGroupByParameter( $groupInstanceOrId );
		if( !$Group )
			return null;
		$Relation = self::getRelation( $userInstanceOrId, $groupInstanceOrId );
		if( $Relation ) {
			return $Relation;
		}
		else {
			$Relation = self::create();
			if( $Relation ) {
				$Relation->userId = $User->getPrimaryKeyValue();
				$Relation->groupId = $Group->getPrimaryKeyValue();
				$Relation->adopt();
			}
			return $Relation;
		}
	}


	/** Findet eine Nutzer-Gruppe-Bindung
	 * @param mixed $userInstanceOrId
	 * @param mixed $groupInstanceOrId
	 * @return UserGroup
	 * @api
	 */
	public static function getRelation( $userInstanceOrId, $groupInstanceOrId ) {
		$User = self::getUserByParameter( $userInstanceOrId );
		if( !$User )
			return null;
		$Group = self::getGroupByParameter( $groupInstanceOrId );
		if( !$Group )
			return null;
		return self::getByColumns( array(
			'userId' => $User->getPrimaryKeyValue(),
			'groupId' => $Group->getPrimaryKeyValue()
		) );
	}


	/** Findet eine Nutzer-Gruppe-Bindung anhand seiner ID
	 * @param integer $datasetId
	 * @return UserGroup
	 * @api
	 */
	public static function getRelationById( $datasetId ) {
		return self::getByPrimaryKey( intval( $datasetId ) );
	}


	/** Findet UserGroups anhand des Users
	 * @param mixed $userInstanceOrId
	 * @return array Array von UserGroup-Instanzen
	 * @api
	 */
	public static function getRelationsByUser( $userInstanceOrId ) {
		$User = self::getUserByParameter( $userInstanceOrId );
		if( !$User )
			return null;
		return self::getByColumn( 'userId', $User->getPrimaryKeyValue(), true );
	}


	/** Findet UserGroups anhand der Gruppe
	 * @param mixed $groupInstanceOrId
	 * @return array Array von UserGroup-Instanzen
	 * @api
	 */
	public static function getRelationsByGroup( $groupInstanceOrId ) {
		$Group = self::getGroupByParameter( $groupInstanceOrId );
		if( !$Group )
			return null;
		return self::getByColumn( 'groupId', $Group->getPrimaryKeyValue(), true );
	}


	/** Gibt die Gruppen zurück, in denen ein Nutzer Mitglied ist
	 * @param mixed $userInstanceOrId
	 * @return array Array von Group-Instanzen
	 * @api
	 */
	public static function getGroupsByUser( $userInstanceOrId ) {
		$groups = array();
		foreach( self::getRelationsByUser( $userInstanceOrId ) as $Relation )
			$groups[] = $Relation->getGroup();
		return $groups;
	}


	/** Gibt die Nutzer zurück, die in einer Gruppe Mitglied sind
	 * @param mixed $groupInstanceOrId
	 * @return array Array von User-Instanzen
	 * @api
	 */
	public static function getUsersByGroup( $groupInstanceOrId ) {
		$users = array();
		foreach( self::getRelationsByGroup( $groupInstanceOrId ) as $Relation )
			$users[] = $Relation->getUser();
		return $users;
	}


/* Public methods */

	public function getUser() {
		return User::getUserById( $this->userId );
	}


	public function getGroup() {
		return Group::getGroupById( $this->groupId );
	}


	public function removeRelation() {
		return self::remove();
	}


/* Private methods */

	/**
	 * @param mixed $instanceOrId
	 * @return User
	 * @internal
	 */
	private static function getUserByParameter( $instanceOrId ) {
		return UserRight::getUserByParameter( $instanceOrId );
	}


	/**
	 * @param mixed $instanceOrId
	 * @return Group
	 * @internal
	 */
	private static function getGroupByParameter( $instanceOrId ) {
		return GroupRight::getGroupByParameter( $instanceOrId );
	}


}