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
interface RightInterface {

	static function getRightById( $datasetId );
	static function getRightsByKey( $key );

	function getJson();
	function getList();
	function inList( $needle );
	function check( $comparisonValue );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
abstract class Right extends \rsCore\DatabaseDatasetAbstract implements RightInterface {

	private $_json;
	private $_list;


/* Static methods */

	/** Findet einen Right anhand seiner ID
	 * @param integer $datasetId
	 * @return Right
	 * @api
	 */
	public static function getRightById( $datasetId ) {
		return self::getByPrimaryKey( intval( $datasetId ) );
	}


	/** Findet Einträge anhand des Rechtebezeichners
	 * @param string $key
	 * @return array Array von Right-Instanzen
	 * @api
	 */
	public static function getRightsByKey( $key ) {
		return self::getByColumn( 'key', $key, true );
	}


	/** Dekodiert das Value-Feld als JSON
	 * @return mixed
	 * @api
	 */
	public function getJson() {
		if( $this->_json === null )
			$this->_json = json_decode( $this->value );
		return $this->_json;
	}


	/** Dekodiert das Value-Feld als kommaseparierte Liste
	 * @return array
	 * @api
	 */
	public function getList() {
		if( $this->_list === null ) {
			$this->_list = array();
			foreach( explode( ',', $this->value ) as $value )
				$this->_list[] = trim( $value );
		}
		return $this->_list;
	}


	/** Prüft, ob ein Wert in der kommaseparierten Liste des Value-Felds vorkommt
	 * @return boolean
	 * @api
	 */
	public function inList( $needle ) {
		return in_array( $needle, $this->getList() );
	}


	/** Prüft, ob das Recht eingeräumt wurde
	 * @return boolean
	 * @api
	 */
	public function check( $comparisonValue=true ) {
		return $this->value == $comparisonValue;
	}


	/**
	 * @param mixed $instanceOrId
	 * @return Group
	 * @internal
	 */
	public static function getGroupByParameter( $instanceOrId ) {
		if( is_object( $instanceOrId ) && $instanceOrId instanceof Group )
			return $instanceOrId;
		elseif( is_int( $instanceOrId ) || intval( $instanceOrId ) > 0 )
			return Group::getById( $instanceOrId );
		return null;
	}


	/**
	 * @param mixed $instanceOrId
	 * @return User
	 * @internal
	 */
	public static function getUserByParameter( $instanceOrId ) {
		if( is_object( $instanceOrId ) && $instanceOrId instanceof User )
			return $instanceOrId;
		elseif( is_int( $instanceOrId ) || intval( $instanceOrId ) > 0 )
			return User::getById( $instanceOrId );
		return null;
	}


}