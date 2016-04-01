<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace rsCore;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface DatabaseNestedSetDatasetAbstractInterface {

	public static function getDatabaseTable();
	public static function getDatabaseConnection();
	public static function getByColumn( $column, $value, $allowMultipleResults, $sorting, $limit );
	public static function getByColumns( $columns, $allowMultipleResults, $sorting, $limit );
	public static function getById( $id );
	public static function getByPrimaryKey( $primaryKey );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
abstract class DatabaseNestedSetDatasetAbstract extends DatabaseNestedSetDataset implements DatabaseNestedSetDatasetAbstractInterface {


	protected static $_databaseTable;


	/* Static methods */

	/** Gibt den Namen der zu nutzenden Datenbank-Tabelle zurück
	 * @return string
	 */
	public static function getDatabaseTable() {
		if( !static::$_databaseTable )
			throw new Exception( __CLASS__ ." must define static variable $"."_databaseTable" );
		return static::$_databaseTable;
	}


	/** Gibt die DatabaseConnector-Instanz zurück
	 * @return DatabaseConnector
	 */
	public static function getDatabaseConnection() {
		return Core::core()->databaseTree( static::getDatabaseTable() );
	}


	/** Sucht nach Datensätzen und instanziiert aus ihnen neue DatabaseDatasetWrapper
	 * @return array
	 */
	public static function getByColumn( $column, $value, $allowMultipleResults=false, $sorting=null, $limit=null ) {
		return static::getByColumns( array($column => $value), $allowMultipleResults, $sorting, $limit );
	}


	/** Sucht nach Datensätzen und instanziiert aus ihnen neue DatabaseDatasetWrapper
	 * @return array
	 */
	public static function getByColumns( $columns, $allowMultipleResults=false, $sorting=null, $limit=null ) {
		return static::getDatabaseConnection()->getByColumns( $columns, $allowMultipleResults, $sorting, $limit );
	}


	/** Alias-Funktion für getByPrimaryKey()
	 * @return object
	 */
	public static function getById( $id ) {
		return static::getByPrimaryKey( intval( $id ) );
	}


	/** Findet einen Datensatz anhand seines Primärschlüssels
	 * @return object
	 */
	public static function getByPrimaryKey( $primaryKey ) {
		return static::getDatabaseConnection()->getByPrimaryKey( $primaryKey );
	}


}