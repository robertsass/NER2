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
interface DatabaseConnectorInterface {

	/* Public methods */
	public function setUtf8();
	public function tableExists( $table );
	public function getTables();
	public function insert( $array );
	public function update( $array, $whereStatement );
	public function updateInsert( $array, $whereStatement );
	public function delete( $whereStatement );
	public function deleteByPrimaryKey( $primaryKey );
	public function get( $sql );
	public function getById( $primaryKey );
	public function getArray( $sql );
	public function getOne( $sql );
	public function getColumns();
	public function getRow( $whereStatement );
	public function getFirstRow();
	public function getLastRow();
	public function getByPrimaryKey( $primaryKey );
	public function getAll( $whereStatement, $something, $join );
	public function getColumn( $column, $whereStatement );
	public function exists( $whereStatement );
	public function execute( $sql );
	public function send( $sql );
	public function count( $condition );
	public function totalCount();
	public function truncate();
	public function popError();

	/* Getter */
	public function getTable();
	public function getLastInsertedId();
	public function getConnection();
	public function getPrimaryKey();
	public function getDatasetHandlerFactory();

	/* Helper functions */
	public static function escape( $value );
	public static function encodeDate( $date );
	public static function encodeDatetime( $date );
	public static function decodeDate( $date );
	public static function decodeDatetime( $date );
	public static function arrayToWhereCondition( $array, $Connection );
	public static function buildAndCondition( array $columns, $placeholder, $Connection );
	public static function buildSortStatement( array $sorting );

	/* Comfort methods */
	public function getByColumn( $column, $value, $allowMultipleResults, $sorting, $limit );
	public function getByColumns( $columns, $allowMultipleResults, $sorting, $limit );
	public function findByColumn( $column, $value, $limit, $sorting );
	public function findByColumns( $columns, $limit, $sorting, $findInText );

	/* DatabaseHandlerFactory methods */
	public function registerHandlerParent( DatabaseHandlerFactoryInterface $HandlerParent );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
abstract class DatabaseConnector extends CoreClass implements DatabaseConnectorInterface, DatabaseHandlerFactoryInterface {


	private $_table;
	private $_primaryKey;
	protected $Connection;
	protected $HandlerParent;
	protected $lastInsertedId;


	protected function __construct( $host, $user, $pass, $database, $table ) {
		$this->_table = $table;
		$this->connect( $host, $user, $pass, $database );

		$this->_primaryKey = $this->initPrimaryKey();
	}


	public function __toString() {
		$datasets = array();
		foreach( $this->getAll() as $Dataset )
			$datasets[] = $Dataset->getColumns();
		return json_encode( $datasets );
	}


	abstract protected function connect( $host, $user, $pass, $database );


	public function deleteByPrimaryKey( $primaryKey ) {
		return $this->delete( '`'. $this->getPrimaryKey() .'` = "'. $primaryKey .'"' );
	}


	/*	Function: get
		Sendet eine SQL-Abfrage und gibt das Ergebnis als rsDatabaseResult-Objekt zurück.

		Parameters:
			$sql - SQL-Abfrage
	*/
	public function get( $sql ) {
		$rows = array();
		$datasetHandlerFactory = $this->getDatasetHandlerFactory();
		$results = $this->getArray( $sql );
		if( $results === null ) {
			$error = Database::popError();
			throw new Exception( $error['text'] );
		}
		foreach( $results as $row ) {
			$datasetHandlerInstance = null;
			if( $datasetHandlerFactory )
				$datasetHandlerInstance = $this->getHandlerInstance( $datasetHandlerFactory, $row );
			if( $datasetHandlerInstance === null )
				$datasetHandlerInstance = new DatabaseDataset( $this, $row );
			$rows[] = $datasetHandlerInstance;
		}
		return $rows;
	}


	public function getById( $primaryKey ) {
		$primaryKey = intval( $primaryKey );
		if( is_int( $primaryKey ) && $primaryKey > 0 )
			return $this->getByPrimaryKey( $primaryKey );
		return null;
	}


	abstract public function getArray( $sql );


	abstract public function getOne( $sql );


	abstract public function getColumn( $column, $whereStatement );


	abstract public function getRow( $whereStatement );


	public function getByPrimaryKey( $primaryKey ) {
		return $this->getRow( '`'. $this->getPrimaryKey() .'` = "'. $primaryKey .'"' );
	}


	public function send( $sql ) {
		return $this->execute( $sql );
	}


	public function truncate() {
		return $this->execute( 'TRUNCATE TABLE `'. $this->getTable() .'`' );
	}


	public function popError() {
		return Database::popError();
	}


	/*	Function: getTable
		Gibt den Namen der ausgewählten Tabelle zurück.
	*/
	public function getTable() {
		return $this->_table;
	}


	/*	Function: getLastInsertedId
		Gibt die ID des zuletzt eingefügten Datensatzes zurück
	*/
	public function getLastInsertedId() {
		return $this->lastInsertedId;
	}


	/*	Function: getConnection
		Gibt den Connection-Handler zurück
	*/
	public function getConnection() {
		return $this->Connection;
	}


	/*	Function: getPrimaryKey
		Gibt den Namen der Spalte, die als Primärschlüssel fungiert, zurück
	*/
	public function getPrimaryKey() {
		return $this->_primaryKey;
	}


	/*	Function: initPrimaryKey
		Holt den Primärschlüssel dieser Tabelle
	*/
	protected function initPrimaryKey() {
		$result = $this->getArray("SHOW KEYS FROM `%TABLE` WHERE Key_name = 'PRIMARY'");
		return $result[0]['Column_name'];
	}


	public function getDatasetHandlerFactory() {
		return Core::core()->getDatabaseDatasetHandler( $this->getTable() );
	}


	public function registerHandlerParent( DatabaseHandlerFactoryInterface $HandlerParent ) {
		$this->HandlerParent = $HandlerParent;
		return $this;
	}


	public function getHandlerInstance( CoreFrameworkHandlerFactory $HandlerFactory, $data ) {
		if( $this->HandlerParent )
			return $this->HandlerParent->getHandlerInstance( $HandlerFactory, $data );
		return $HandlerFactory->getHandlerInstance( $this, $data );
	}


	/* Helper functions */

	public static function escape( $value ) {
		\rsCore\Core::core()->database()->getConnection()->escape_string( stripslashes( $value ) );
	}


	public static function encodeDate( $date ) {
		if( is_int( $date ) )
			$date = \DateTime::createFromFormat( 'U', $date );
		if( $date instanceof \DateTime )
			return $date->format( 'Y-m-d' );
		if( $date instanceof \rsCore\Calendar )
			return $date->format( 'Y-m-d', false );
		return $date;
	}


	public static function encodeDatetime( $date ) {
		if( is_int( $date ) )
			$date = \DateTime::createFromFormat( 'U', $date );
		if( $date instanceof \DateTime )
			return $date->format( 'Y-m-d H:i:s' );
		if( $date instanceof \rsCore\Calendar )
			return $date->format( 'Y-m-d H:i:s', false );
		return $date;
	}


	public static function decodeDate( $date ) {
		return \DateTime::createFromFormat( 'Y-m-d', $date );
	}


	public static function decodeDatetime( $date ) {
		return \DateTime::createFromFormat( 'Y-m-d H:i:s', $date );
	}


	public static function arrayToWhereCondition( $array, $Connection=null ) {
		$condition = array();
		foreach( $array as $field => $value ) {
			$value = $Connection !== null ? $Connection->escape_string( stripslashes( $value ) ) : static::escape( $value );
			$condition[] = '`'. $field .'`="'. $value .'"';
		}
		return implode( ' AND ', $condition );
	}


	public static function buildAndCondition( array $columns, $placeholder=0, $Connection=null ) {
		$sql = '';
		$conditions = array();
		foreach( $columns as $column => $value ) {
			$value = $Connection !== null ? $Connection->escape_string( stripslashes( $value ) ) : static::escape( $value );
			$conditions[] = $placeholder > 0 ? 'LOWER(`'. $column .'`) LIKE "'. ($placeholder == 2 ? '%' : ''). strtolower( $value ) .'%"' : '`'. $column .'`="'. $value .'"';
		}
		$sql = implode( ' AND ', $conditions );
		return $sql;
	}


	public static function buildSortStatement( array $sorting ) {
		$sql = '';
		$statements = array();
		foreach( $sorting as $field => $order )
			$statements[] = '`'. $field .'` '. $order;
		if( !empty( $statements ) )
			$sql = 'ORDER BY '. implode( ', ', $statements );
		return $sql;
	}


	/* Comfort methods */

	public function getByColumn( $column, $value, $allowMultipleResults=false, $sorting=null, $limit=null, $start=null ) {
		return $this->getByColumns( array( $column => $value ), $allowMultipleResults, $sorting, $limit, $start );
	}


	public function getByColumns( $columns, $allowMultipleResults=false, $sorting=null, $limit=null, $start=null ) {
		$column = key( $columns );
		$value = current( $columns );
		$condition = self::buildAndCondition( $columns, 0, $this->getConnection() );

		if( $sorting === null )
			$sorting = array();

		$sortCondition = '';
		if( !empty( $sorting ) ) {
			$sortCondition = array();
			foreach( $sorting as $field => $direction ) {
				$sortCondition[] = '`'. $field .'` '. ($direction == 'ASC' ? 'ASC' : 'DESC');
			}
			$sortCondition = 'ORDER BY '. implode( ', ', $sortCondition );
		}

		if( $allowMultipleResults ) {
			if( $limit > 0 )
				$sortCondition .= ' LIMIT '. intval( $start ) .','. intval( $limit );
			return $this->getAll( $condition, $sortCondition );
		}
		else {
			$sortCondition .= ' LIMIT '. intval( $start ) .',1';
			$results = $this->getAll( $condition, $sortCondition );
			if( empty($results) )
				return null;
			return current( $results );
		}
	}


	public function findByColumn( $column, $value, $limit=10, $sorting=null ) {
		return $this->findByColumns( array( $column => $value ), $limit, $sorting );
	}


	public function findByColumns( $columns, $limit=10, $sorting=null, $findInText=false ) {
		if( $sorting === null ) {
			$sorting = array();
			foreach( $columns as $field => $value )
				$sorting[ $field ] = 'ASC';
		}

		$column = key( $columns );
		$value = current( $columns );
		$condition = self::buildAndCondition( $columns, $findInText ? 2 : 1, $this->getConnection() );
		$sorting = self::buildSortStatement( $sorting );

		return $this->getAll( $condition, $sorting .' LIMIT 0,'. intval( $limit ) );
	}



}