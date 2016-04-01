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
interface GroupRightInterface {

	static function addRight( $key, $value, $groupInstanceOrId, $throwExceptions );
	static function getRightById( $datasetId );
	static function getRightByKey( $key, $groupInstanceOrId, $createIfDoesNotExist );
	static function getRightsByGroup( $groupInstanceOrId );

	function getJson();
	function getList();
	function inList( $needle );
	function check( $comparisonValue );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class GroupRight extends Right implements GroupRightInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-group-rights';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Vermerkt ein Recht für einen Nutzer
	 * @param string $key Rechtebezeichner
	 * @param string $value
	 * @param mixed $groupInstanceOrId
	 * @param boolean $throwExceptions
	 * @return GroupRight
	 * @api
	 */
	public static function addRight( $key, $value, $groupInstanceOrId, $throwExceptions=true ) {
		$Group = self::getGroupByParameter( $groupInstanceOrId );
		if( !$Group )
			return null;
		if( static::getRightByKey( $key, $Group ) ) {
			if( $throwExceptions )
				throw new \rsCore\Exception( "This right is already defined for this group." );
			return null;
		}
		else {
			$GroupRight = self::create();
			if( $GroupRight ) {
				$GroupRight->key = $key;
				$GroupRight->value = $value;
				$GroupRight->groupId = $Group->getPrimaryKeyValue();
				$GroupRight->adopt();
			}
			return $GroupRight;
		}
	}


	/** Findet für einen Group eine GroupRight-Instanz anhand des Rechtebezeichners
	 * @param string $key
	 * @param mixed $groupInstanceOrId
	 * @param boolean $createIfDoesNotExist
	 * @return GroupRight
	 * @api
	 */
	public static function getRightByKey( $key, $groupInstanceOrId, $createIfDoesNotExist=false ) {
		$Group = self::getGroupByParameter( $groupInstanceOrId );
		if( !$Group )
			return null;
		$GroupRight = self::getByColumns( array(
			'groupId' => $Group->getPrimaryKeyValue(),
			'key' => $key
		) );
		if( !$GroupRight && $createIfDoesNotExist )
			return static::addRight( $key, null, $Group, false );
		return $GroupRight;
	}


	/** Findet GroupRights anhand des Groups
	 * @param mixed $groupInstanceOrId
	 * @return array Array von GroupRight-Instanzen
	 * @api
	 */
	public static function getRightsByGroup( $groupInstanceOrId ) {
		$Group = self::getGroupByParameter( $groupInstanceOrId );
		if( !$Group )
			return null;
		return self::getByColumn( 'groupId', intval( $Group->getPrimaryKeyValue() ), true );
	}


}