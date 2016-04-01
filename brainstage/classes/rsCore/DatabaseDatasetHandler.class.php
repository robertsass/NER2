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
 */
abstract class DatabaseDatasetHandler extends CoreClass {


	private $data = null;
	private $changed = false;
	private $errors = array();
	private $removed = false;
	private $inMagicMethod = false;


	protected static function myTable() {
		return static::TABLE;
	}


	protected static function myPrimaryKey() {
		return static::PRIMARY_KEY;
	}


	public static function db( $returnAsObject=true, $returnIntKeys=false ) {
		return rsMysql::instance( static::myTable() )
				->return_int_keys( $returnIntKeys )
				->return_as_object( $returnAsObject );
	}


	protected static function create( $primaryKey=null ) {
		$initValues = array();
		if( $primaryKey !== null )
			$initValues[ self::myPrimaryKey() ] = $primaryKey;
		$success = self::db()->insert( $initValues );
		if( $success ) {
			if( $primaryKey !== null )
				$id = $primaryKey;
			else
				$id = self::db()->get_last_insert_id();
			return new static( $id );
		}
		return null;
	}


	public static function exists( $id ) {
		return self::db()->exists( '`'. self::myPrimaryKey() .'`="'. intval( $id ) .'"' );
	}


	public static function count( $condition ) {
		if( is_array($condition) )
			$condition = self::buildAndCondition( $condition );
		elseif( !is_string($condition) )
			return null;
		$count = self::db(false)->getOne( 'SELECT COUNT(*) FROM `%TABLE` WHERE '. $condition );
		return intval( current($count) );
	}


	public static function totalCount() {
		$count = self::db(false)->getOne( 'SELECT COUNT(*) FROM `%TABLE`' );
		return intval( current($count) );
	}


	public static function fetchBySQL( $sql ) {
		return self::instantiateResultset( self::db(false)->get( $sql ) );
	}


	public static function getAll( $extension=null ) {
		$objects = array();
		$results = self::db(false)->getAll( null, $extension );
		foreach( $results as $result )
			$objects[] = new static( $result );
		return $objects;
	}


	public static function getById( $id ) {
		$id = intval( $id );
		if( !self::exists( $id ) )
			return null;
		return new static( $id );
	}


	public static function getByColumn( $column, $value, $allowMultipleResults=false, $sorting=null, $limit=null ) {
		return self::getByColumns( array( $column => $value ), $allowMultipleResults, $sorting, $limit );
	}


	public static function getByColumns( $columns, $allowMultipleResults=false, $sorting=null, $limit=null ) {
		$column = key( $columns );
		$value = current( $columns );
		$condition = self::buildAndCondition( $columns );

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
				$sortCondition .= ' LIMIT 0,'. intval( $limit );
			$results = self::db(false)->getAll( $condition, $sortCondition );
			$objects = array();
			foreach( $results as $data ) {
				$Object = new static( $data );
				if( $Object !== null )
					$objects[] = $Object;
			}
			return $objects;
		}
		else {
			$data = self::db(false)->getRow( $condition, 'ORDER BY `id` ASC' );
			if( $data[ $column ] == $value )
				return new static( $data );
			return null;
		}
	}


	public static function findByColumn( $column, $value, $limit=10, $sorting=null ) {
		return self::findByColumns( array( $column => $value ), $limit, $sorting );
	}


	public static function findByColumns( $columns, $limit=10, $sorting=null, $findInText=false ) {
		if( $sorting === null ) {
			$sorting = array();
			foreach( $columns as $field => $value )
				$sorting[ $field ] = 'ASC';
		}

		$column = key( $columns );
		$value = current( $columns );
		$condition = self::buildAndCondition( $columns, $findInText ? 2 : 1 );
		$sorting = self::buildSortStatement( $sorting );

		$results = self::db(false)->getAll( $condition, $sorting .' LIMIT 0,'. intval( $limit ) );
		$objects = array();
		foreach( $results as $data ) {
			if( substr_count( strtolower( $data[ $column ] ), strtolower( $value ) ) > 0 ) {
				$Object = new static( $data );
				if( $Object !== null )
					$objects[] = $Object;
			}
		}
		return $objects;
	}


	protected static function buildAndCondition( array $columns, $placeholder=0 ) {
		$sql = '';
		$conditions = array();
		foreach( $columns as $column => $value )
			$conditions[] = $placeholder > 0 ? 'LOWER(`'. $column .'`) LIKE "'. ($placeholder == 2 ? '%' : ''). strtolower( $value ) .'%"' : '`'. $column .'`="'. $value .'"';
		$sql = implode( ' AND ', $conditions );
		return $sql;
	}


	protected static function buildSortStatement( array $sorting ) {
		$sql = '';
		$statements = array();
		foreach( $sorting as $field => $order )
			$statements[] = '`'. $field .'` '. $order;
		if( !empty( $statements ) )
			$sql = 'ORDER BY '. implode( ', ', $statements );
		return $sql;
	}


	protected static function instantiateResultset( array $resultset ) {
		$objects = array();
		foreach( $resultset as $row ) {
			$Object = null;
			if( is_array( $row ) ) {
				if( array_key_exists( static::myPrimaryKey(), $row ) )
					$Object = self::getById( $row[ static::myPrimaryKey() ] );
				else
					$Object = new static( $row );
			}
			else
				$Object = self::getById( $row );
			if( $Object !== null )
				$objects[] = $Object;
		}
		return $objects;
	}


	protected function __construct( $data ) {
		if( !is_array( $data ) )
			if( intval($data) > 0 )
				$data = $this->getData( intval($data) );
			else throw new Exception( 'First parameter of '. __CLASS__ .' must be array or integer.' );
		$this->initData( $data );
	}


	public function __destruct() {
		$this->adopt();
	}


	private function getWhereCondition() {
		return '`'. self::myPrimaryKey() .'`="'. $this->get( self::myPrimaryKey() ) .'"';
	}


	protected function getData( $key ) {
		if( $this->data !== null )
			return false;
		$data = self::db(false)->getRow( '`'. self::myPrimaryKey() .'`="'. $key .'"' );
		return $data;
	}


	protected function initData( array $data=null ) {
		if( $this->data !== null )
			return false;
		$this->data = $data;
		return true;
	}


	public function __get( $key ) {
		$this->inMagicMethod = true;
		$value = $this->get( $key );
		$this->inMagicMethod = false;
		return $value;
	}


	public function get( $key ) {
		if( !$this->removed && is_array( $this->data ) && array_key_exists( $key, $this->data ) )
			return $this->decode( $key, $this->data[ $key ] );
		return null;
	}


	public function __set( $key, $value ) {
		$this->inMagicMethod = true;
		$return = $this->set( $key, $value );
		$this->inMagicMethod = false;
		return $return;
	}


	public function set( $key, $value ) {
		if( array_key_exists( $key, $this->data ) ) {
			if( $key !== $this->myPrimaryKey() ) {
				if( $this->validate( $key, $value ) ) {
					$this->data[ $key ] = $this->encode( $key, $value );
					$this->changed = true;
					return true;
				} elseif( $this->inMagicMethod )
					throw new Exception( 'The field "'. $key .'" is not valid.' );
			} elseif( $this->inMagicMethod )
				throw new Exception( 'You can\'t change the primary key, it would break dataset connections.' );
		} elseif( $this->inMagicMethod )
			throw new Exception( 'The field "'. $key .'" does not exist.' );
		return false;
	}


	protected function encode( $key, $value ) {
		$method_name = 'encode'. ucfirst( $key );
		if( method_exists( $this, $method_name ) )
			return call_user_func_array( array( $this, $method_name ), array( $value ) );
		return $value;
	}


	protected function decode( $key, $value ) {
		$method_name = 'decode'. ucfirst( $key );
		if( method_exists( $this, $method_name ) )
			return call_user_func_array( array( $this, $method_name ), array( $value ) );
		return $value;
	}


	protected function validate( $key, $value ) {
		$method_name = 'validate'. ucfirst( $key );
		if( method_exists( $this, $method_name ) )
			return call_user_func_array( array( $this, $method_name ), array( $value ) );
		return true;
	}


	protected function onChange() {}


	protected function adoptChanges() {
		if( $this->changed )
			return rsMysql::instance( static::myTable() )->update( $this->data, $this->getWhereCondition() );
		return null;
	}


	public function adopt() {
		if( $this->removed ) {
			throw new Exception( 'Dataset can\'t be changed, because it has been removed during lifetime.' );
			return false;
		}
		if( $this->changed )
			$this->onChange();
		return $this->adoptChanges();
	}


	public function remove() {
		if( self::db()->delete( $this->getWhereCondition() ) ) {
			$this->removed = true;
			return true;
		}
		return false;
	}


	public function getErrors() {
		return $this->errors;
	}


	public function failed( $clearErrors=false ) {
		$failed = !empty( $this->errors );
		if( $clearErrors )
			$this->clearErrors();
		return $failed;
	}


	public function clearErrors() {
		$this->errors = array();
	}


	public function toArray() {
		return $this->data;
	}


}