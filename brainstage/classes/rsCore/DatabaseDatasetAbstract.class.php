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
interface DatabaseDatasetAbstractInterface {

	public static function getDatabaseTable();
	public static function getDatabaseConnection();
	public static function getByColumn( $column, $value, $allowMultipleResults, $sorting, $limit );
	public static function getByColumns( $columns, $allowMultipleResults, $sorting, $limit );
	public static function getById( $id );
	public static function getByPrimaryKey( $primaryKey );

	public static function enableDatasetCaching( $boolean );
	public static function isDatasetCachingEnabled();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
abstract class DatabaseDatasetAbstract extends DatabaseDataset implements DatabaseDatasetAbstractInterface {


	protected static $_databaseTable;
	private static $_cachedDatasets;
	private static $_datasetCachingEnabled;


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
		return Core::core()->database( static::getDatabaseTable() );
	}


	/** Fügt einen neuen Datensatz ein und gibt die repräsentierende Instanz zurück
	 * @return DatabaseDatasetAbstract
	 */
	protected static function create() {
		$NewInstance = self::getDatabaseConnection()->insert( array() );
		if( $NewInstance ) {
			return $NewInstance;
		}
		return null;
	}


	/** Prüft, ob ein Datensatz mit dem gegebenen Primärschlüssel existiert
	 * @param integer $id
	 * @return boolean
	 */
	public static function exists( $id ) {
		return self::getDatabaseConnection()->exists( '`'. self::myPrimaryKey() .'`="'. intval( $id ) .'"' );
	}


	/** Zählt die Datensätze, die auf die gegebene Bedingung zutreffen
	 * @param string $condition
	 * @return integer
	 */
	public static function count( $condition ) {
		return self::getDatabaseConnection()->count( $condition );
	}


	/** Zählt die Datensätze in der Tabelle
	 * @return integer
	 */
	public static function totalCount() {
		return self::getDatabaseConnection()->totalCount();
	}


	/** Gibt alle Datensätze zurück
	 * @param string $condition
	 * @return array Array von DatabaseDatasetAbstract-Instanzen
	 */
	public static function getAll( $condition=null ) {
		return self::getDatabaseConnection()->getAll( $condition );
	}


	/** Sucht nach Datensätzen und instanziiert aus ihnen neue DatabaseDatasetWrapper
	 * @return array
	 */
	public static function getByColumn( $column, $value, $allowMultipleResults=false, $sorting=null, $limit=null, $start=null ) {
		return static::getByColumns( array($column => $value), $allowMultipleResults, $sorting, $limit, $start );
	}


	/** Sucht nach Datensätzen und instanziiert aus ihnen neue DatabaseDatasetWrapper
	 * @return array
	 */
	public static function getByColumns( $columns, $allowMultipleResults=false, $sorting=null, $limit=null, $start=null ) {
		$cachedDataset = (!$sorting && !$limit) ? self::getCachedDataset( $columns, $allowMultipleResults ) : null;
		if( $cachedDataset )
			return $cachedDataset;
		$Dataset = static::getDatabaseConnection()->getByColumns( $columns, $allowMultipleResults, $sorting, $limit, $start );
		self::cacheDataset( $columns, $Dataset );
		return $Dataset;
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


	/** Aktiviert oder deaktiviert das Caching auf Ebene von Datensätzen
	 */
	public static function enableDatasetCaching( $boolean=true ) {
		self::$_datasetCachingEnabled = $boolean ? true : false;
	}


	/** Gibt zurück, ob das Caching von Datensätzen aktiviert ist
	 */
	public static function isDatasetCachingEnabled() {
		if( self::$_datasetCachingEnabled === null ) {
			return in_array( 'rsCore\\DatabaseDatasetCachingInterface', class_implements( get_called_class() ) );
		}
		return self::$_datasetCachingEnabled ? true : false;
	}


/* Private methods */

	/** Legt einen Datensatz im statischen Cache ab
	 * @param mixed $identifier
	 * @param DatabaseDatasetAbstract $dataset
	 * @internal
	 */
	protected static function cacheDataset( $identifier, $dataset ) {
		if( !self::isDatasetCachingEnabled() )
			return null;
		if( is_array( $identifier ) )
			$identifier = json_encode( $identifier );
		if( self::$_cachedDatasets === null )
			self::$_cachedDatasets = array();
		$multipleResultsKey = is_array( $dataset ) ? 'multi' : 'single';
		self::$_cachedDatasets[ $identifier ][ $multipleResultsKey ] = $dataset;
	}


	/** Liest einen Datensatz aus dem statischen Cache
	 * @param mixed $identifier
	 * @return DatabaseDatasetAbstract
	 * @internal
	 */
	protected static function getCachedDataset( $identifier, $multipleResults=false ) {
		if( !self::isDatasetCachingEnabled() )
			return null;
		if( is_array( $identifier ) )
			$identifier = json_encode( $identifier );
		elseif( !is_string( $identifier ) )
			return null;
		$multipleResultsKey = $multipleResults ? 'multi' : 'single';
		if( self::$_cachedDatasets === null )
			self::$_cachedDatasets = array();
		if( array_key_exists( $identifier, self::$_cachedDatasets ) ) {
			$identifierCache = self::$_cachedDatasets[ $identifier ];
			if( array_key_exists( $multipleResultsKey, $identifierCache ) )
				return $identifierCache[ $multipleResultsKey ];
			return null;
		}
		return null;
	}


}