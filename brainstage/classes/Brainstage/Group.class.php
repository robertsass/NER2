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
interface GroupInterface {

	static function addGroup( $name, $throwExceptions );

	static function getGroupById( $groupId );
	static function getGroupByName( $name );
	static function getGroups( $limit, $start );

	function may( $key, $comparisonValue );
	function mayUseClass( $className );
	function getRight( $key );
	function getRights();
	function getPluginSpecificRight( $pluginName, $key );

	function getMembers();

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Group extends \rsCore\DatabaseDatasetAbstract implements GroupInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-groups';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Erzeugt einen neuen Group
	 * @param string $email
	 * @param string $password
	 * @return Group
	 * @api
	 */
	public static function addGroup( $name, $throwExceptions=true ) {
		if( static::getGroupByName( $name ) ) {
			if( $throwExceptions )
				throw new \rsCore\Exception( "Another group is already named like that." );
			return null;
		}
		else {
			$Group = self::create();
			if( $Group ) {
				$Group->name = $name;
				$Group->adopt();
			}
			return $Group;
		}
	}


	/** Findet einen Group anhand seiner ID
	 * @param integer $groupId
	 * @return Group
	 * @api
	 */
	public static function getGroupById( $groupId ) {
		return self::getByPrimaryKey( intval( $groupId ) );
	}


	/** Findet eine Gruppe anhand des Namens
	 * @param string $name
	 * @return Group
	 * @api
	 */
	public static function getGroupByName( $name ) {
		return self::getByColumn( 'name', $name, false );
	}


	/** Holt alle Gruppen
	 * @param integer $limit
	 * @param integer $start
	 * @return array Array von Group-Instanzen
	 * @api
	 */
	public static function getGroups( $limit=null, $start=0 ) {
		return self::getDatabaseConnection()->getAll( null, 'ORDER BY `id` DESC ' .($limit !== null ? 'LIMIT '. intval($start) .','. intval($limit) : '') );
	}


	/** Prüft einen Rechtebezeichner auf Existenz oder einen bestimmten Wert
	 * @param string $key Rechtebezeichner
	 * @param mixed $comparisonValue Vergleichswert
	 * @return boolean
	 * @api
	 */
	public function may( $key, $comparisonValue=true ) {
		if( $this->isSuperAdmin() )
			return true;

		// Replace Backslashes in Namespace Path
		$key = ltrim( $key, '\\' );
		$key = str_replace( '\\', '/', $key );

		$Right = $this->getRight( $key );
		if( $Right && $Right->check( $comparisonValue ) )
			return true;
		return !$comparisonValue;
	}


	/** Prüft ob der Nutzer eine Klasse nutzen darf
	 * @param string $className Klassenbezeichnung samt Namespace
	 * @return boolean
	 * @api
	 */
	public function mayUseClass( $className ) {
		return $this->isSuperAdmin() || $this->may( $className );
	}


	/** Prüft ob der Nutzer das Superadmin-Recht hat
	 * @return boolean
	 * @api
	 */
	public function isSuperAdmin() {
		$Right = $this->getRight( 'Brainstage:superadmin' );
		if( $Right && $Right->value )
			return true;
		return false;
	}


	/** Gibt zu dem gegebenen Rechtebezeichner eine Instanz von GroupRight zurück
	 * @param string $key Rechtebezeichner
	 * @return GroupRight
	 * @api
	 */
	public function getRight( $key ) {
		return GroupRight::getRightByKey( $key, $this );
	}


	/** Gibt die Rechte des Nutzers zurück
	 * @return array Array von GroupRight-Instanzen
	 * @api
	 */
	public function getRights() {
		return GroupRight::getRightsByGroup( $this );
	}


	/** Gibt zu dem gegebenen Plugin und Rechtebezeichner eine Instanz von GroupRight zurück
	 * @param string $pluginName Pluginname
	 * @param string $key Rechtebezeichner
	 * @return GroupRight
	 * @api
	 */
	public function getPluginSpecificRight( $pluginName, $key ) {
		// Replace Backslashes in Namespace Path
		$pluginName = ltrim( $pluginName, '\\' );
		$pluginName = str_replace( '\\', '/', $pluginName );

		$right = $pluginName .':'. $key;
		return GroupRight::getRightByKey( $right, $this );
	}


	/** Gibt die Mitglieder zurück
	 * @return array Array von User-Instanzen
	 * @api
	 */
	public function getMembers() {
		return UserGroup::getUsersByGroup( $this );
	}


	/** Entfernt den Group
	 * @return boolean
	 * @api
	 * @todo Ggf. den Platform-Group mitlöschen, mindestens an das Platform-Group-Objekt eine Notification über die Löschung senden
	 */
	public function remove() {
		foreach( UserGroup::getRelationsByGroup( $this ) as $Relation )
			$Relation->removeRelation();
		foreach( self::getRights() as $Right )
			$Right->remove();
		return parent::remove();
	}


/* Protected methods */


}