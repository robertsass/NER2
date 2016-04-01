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
interface DatabaseInterface {

	public static function instance( $table, $usePrefix, $host, $user, $pass, $database );
	public static function connect( $table, $usePrefix, $host, $user, $pass, $database );
	public static function table( $table, $usePrefix, $host, $user, $pass, $database );

	public static function new_instance( $host, $user, $pass, $database, $table, $type );

	public static function singleton();
	public static function table_exists( $tablename );

	public static function logError( $text, $sql, $number, $dataset );
	public static function popError();
	public static function report();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Database extends CoreClass implements DatabaseInterface {


	const MYSQL		= 'Type:MySQL';
	const POSTGRE	= 'Type:Postgre';

	const ERRNO_1	= 'Unknown database type given';


	protected static $singleton		= null;
	protected static $connections	= array();
	protected static $errors		= array();


	/* Static methods */

	public static function instance( $table, $usePrefix=true, $host=null, $user=null, $pass=null, $database=null ) {
		$host = $host ? $host : DBHOST;
		$user = $user ? $user : DBUSER;
		$pass = $pass ? $pass : DBPASS;
		$database = $database ? $database : DBNAME;
		$tablename = ($usePrefix ? DBPREFIX : ''). $table;
		$instanceKey = $user .'@'. $host .'/'. $database .':'. $tablename;

		if( !array_key_exists( $instanceKey, self::$connections ) )
			self::$connections[ $instanceKey ] = self::new_instance( $host, $user, $pass, $database, $tablename );
		return self::$connections[ $instanceKey ];
	}

	public static function connect( $table, $usePrefix=true, $host=null, $user=null, $pass=null, $database=null ) {
		return self::instance( $table, $usePrefix, $host, $user, $pass, $database );
	}

	public static function table( $table, $usePrefix=true, $host=null, $user=null, $pass=null, $database=null ) {
		return self::instance( $table, $usePrefix, $host, $user, $pass, $database );
	}


	public static function new_instance( $host, $user, $pass, $database, $table, $type=null ) {
		if( $type !== null ) {
			if( $type == self::MYSQL )
				return new DatabaseConnectorMysql( $host, $user, $pass, $database, $table );
			if( $type == self::POSTGRE )
				return new DatabaseConnectorPostgre( $host, $user, $pass, $database, $table );

			throw new Exception( ERRNO_1. ': '. $type, 1 );
		}

		if( defined('DB_POSTGRE') && DB_POSTGRE == true )
			return new DatabaseConnectorPostgre( $host, $user, $pass, $database, $table );
		return new DatabaseConnectorMysql( $host, $user, $pass, $database, $table );
	}


	public static function singleton() {
		if( self::$singleton === null )
			self::$singleton = new self();
		return self::$singleton;
	}


	public static function table_exists( $tablename ) {
		if( empty( self::$connections ) )
			$Connection = self::instance( null, false );
		else
			$Connection = current( self::$connections );
		return $Connection->table_exists( $tablename );
	}


	/* Constructor & Destructor */

	private function __construct() {}


	public function __destruct() {
		if( !empty( self::$errors ) )
			echo self::report();
	}


	/* Public methods */

	public function __get( $table ) {
		return self::instance( $table );
	}


	public function __call( $table, $args ) {
		return self::instance( $table, isset($args[0]) && $args[0] ? false : true );
	}


	public static function __callStatic( $table, $args ) {
		return self::instance( $table, isset($args[0]) && $args[0] ? false : true );
	}


	public static function logError( $text, $sql, $number=null, $dataset=null ) {
		$error = array();
		$error['text']		= $text;
		$error['sql']		= $sql;
		if( $number )
			$error['number']	= $number;
		if( $dataset )
			$error['dataset']	= $dataset;
		self::$errors[]	= $error;
	}


	public static function popError() {
		return array_pop( self::$errors );
	}


	public static function report() {
		print_r( self::$errors );
		return self::$errors;
	}


}