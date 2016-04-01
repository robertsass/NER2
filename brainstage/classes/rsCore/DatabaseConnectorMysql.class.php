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
class DatabaseConnectorMysql extends DatabaseConnector {


	public function __construct( $host, $user, $pass, $database, $table, $utf8=true ) {
		parent::__construct( $host, $user, $pass, $database, $table );

		if( $utf8 )
			$this->setUtf8();
	}


	protected function connect( $host, $user, $pass, $database ) {
		$this->Connection = new \mysqli( $host, $user, $pass, $database );
	}


	/* Gibt true zurück, wenn die ausgewählte Tabelle existiert.
	 * @return array
	*/
	public function getTables() {
		$tables = array();
		foreach( $this->getArray( 'SHOW TABLES' ) as $dataset ) {
			$tableName = current( $dataset );
			if( $tableName )
				$tables[] = $tableName;
		}
		return $tables;
	}


	/*	Function: tableExists
		Gibt true zurück, wenn die ausgewählte Tabelle existiert.
	*/
	public function tableExists( $table=null ) {
		$table = $this->getTable();
		$tables = $this->getArray( 'SHOW TABLES LIKE "'. $table .'"' );
		if( empty($tables[0]) )
			return false;
		return true;
	}


	/*	Function: setUtf8
		Setzt die Datenbank-Verbindung nachträglich noch auf UTF-8
	*/
	public function setUtf8() {
		$this->getConnection()->set_charset( 'utf8' );
	}


	/*	Function: execute
		Sendet eine SQL-Anweisung an die Datenbank und gibt die Ressource oder wenn ein Fehler auftrat false zurück.

		Parameters:
			$sql - SQL-Query
	*/
	public function execute( $sql ) {
		$sql = str_replace( '%TABLE', $this->getTable(), $sql );
		if( $res = $this->getConnection()->query( $sql ) OR $res = Database::logError( mysqli_error($this->getConnection()), $sql, mysqli_errno($this->getConnection()) ) )
			return $res;
		else
			return false;
	}


	/*	Function: insert
		Fügt einen Datensatz in die Tabelle ein.

		Parameters:
			$array - Einzufügender Datensatz (Array mit Datenbankfeld => Wert)
	*/
	public function insert( $array ) {
		$columns = array_keys($array);
		$values = $array;
		$first = true;
		$sql = 'INSERT INTO `' . $this->getTable() . '`(';
		foreach($columns as $column) {
			if(!$first)
				$sql .= ',';
			$sql .= '`' . $column . '`';
			$first = false;
		}
		$sql .= ') ';
		$sql .= 'VALUES(';
		$first = true;
		foreach($values as $value) {
			if(!$first)
				$sql .= ',';
			$sql .= '"' . $this->Connection->escape_string( stripslashes( $value ) ) . '"';
			$first = false;
		}
		$sql .= ');';
		$this->lastInsertedId = null;
		if( $this->send($sql) == false )
			return false;
		$this->lastInsertedId = $this->Connection->insert_id;
		return $this->getByPrimaryKey( $this->getLastInsertedId() );
	}


	/*	Function: update
		Aktualisiert einen Datensatz in der Tabelle.

		Parameters:
			$array - Aktualisierte Felder (Array mit Datenbankfeld => Wert)
			$where - WHERE-Statement
	*/
	public function update( $array, $whereStatement ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		$first = true;
		$sql = 'UPDATE `' . $this->getTable() . '` SET ';
		foreach($array as $spalte => $value) {
			if(!$first) $sql .= ', ';
			$sql .= '`' . $spalte . '`' . '=';
			if( $value === null )
				$sql .= 'NULL';
			else
				$sql .= '"' . $this->Connection->escape_string( stripslashes( $value ) ) . '"';
			$first = false;
		}
		$sql .= ' WHERE ' . $whereStatement;
		if( $this->send($sql) == false )
			return false;
		return true;
	}


	/*	Function: updateInsert
		Aktualisiert einen Datensatz in der Tabelle oder fügt ihn neu ein, falls er noch nicht existiert.

		Parameters:
			$array - Datenbankfelder (Array mit Datenbankfeld => Wert)
			$where - WHERE-Statement
	*/
	public function updateInsert( $array, $whereStatement ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		$row = $this->getOne('SELECT * FROM `' . $this->getTable() . '` WHERE ' . $whereStatement .' LIMIT 0,1');
		if($row[0] !== null)
			return $this->update( $array, $whereStatement );
		else
			return $this->insert( $array );
	}


	/*	Function: delete
		Löscht einen Datensatz.

		Parameters:
			$where - WHERE-Statement
	*/
	public function delete( $whereStatement ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		if( $this->send('DELETE FROM `' . $this->getTable() . '` WHERE ' . $whereStatement) )
			return true;
		else return false;
	}


	/*	Function: getArray
		Sendet eine SQL-Abfrage und gibt das Ergebnis als Array zurück.

		Parameters:
			$sql - SQL-Abfrage
	*/
	public function getArray( $sql ) {
		$sql = str_replace( '%TABLE', $this->getTable(), $sql );
		if( $Result = $this->getConnection()->query( $sql ) OR Database::logError( mysqli_error($this->getConnection()), $sql, mysqli_errno($this->getConnection()) ) ) {
			$rows = array();
			while( $row = $Result->fetch_assoc() )
				$rows[] = $row;
			return $rows;
		}
		return null;
	}


	public function getOne( $sql ) {
		$sql = rtrim( $sql, ';' ) .' LIMIT 0,1;';
		$result = $this->get( $sql );
		if( !empty($result) )
			return $result[0];
		return null;
	}


	/*	Function: getColumns
		Gibt ein Array aller Spalten der ausgewählten Tabelle zurück.
	*/
	public function getColumns() {
		$row = $this->get('SHOW COLUMNS FROM `' . $this->getTable() . '`');
		return $row;
	}


	/*	Function: getRow
		Gibt einen zutreffenden Datensatz zurück.

		Parameters:
			$whereStatement - WHERE-Statement
	*/
	public function getRow( $whereStatement ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		$result = $this->getOne('SELECT * FROM `' . $this->getTable() . '` WHERE ' . $whereStatement);
		return $result;
	}


	/*	Function: getFirstRow
		Gibt den ersten Datensatz der Tabelle zurück.
	*/
	public function getFirstRow() {
		return $this->getOne('SELECT * FROM `' . $this->getTable() . '` ORDER BY `'. $this->getPrimaryKey() .'` ASC');
	}


	/*	Function: getLastRow
		Gibt den letzten Datensatz der Tabelle zurück.
	*/
	public function getLastRow() {
		return $this->getOne('SELECT * FROM `' . $this->getTable() . '` ORDER BY `'. $this->getPrimaryKey() .'` DESC');
	}


	/*	Function: getAll
		Gibt alle zutreffenden Datensätze zurück.

		Parameters:
			$whereStatement - Optional: WHERE-Statement
			$something - Optional: Weitere Parameter, z.B. ORDER BY-Statement
	*/
	public function getAll( $whereStatement=null, $something=null, $join=null ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		$Result = $this->get( 'SELECT * FROM `' . $this->getTable() . '`'. ($join !== null ? ' '. $join : '') . ($whereStatement !== null ? ' WHERE ' . $whereStatement : '') . ($something !== null ? ' '. $something : '') );
		return $Result;
	}


	/*	Function: getColumn
		Gibt ein Datenbankfeld des zutreffenden Datensatzes zurück.

		Parameters:
			$whereStatement - WHERE-Statement
	*/
	public function getColumn( $column, $whereStatement ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		$result = $this->getOne('SELECT `' . $column . '` FROM `' . $this->getTable() . '` WHERE ' . $whereStatement);
		return $result[0];
	}


	/*	Function: exists
		Gibt true zurück, wenn die WHERE-Bedingung ein oder mehrere Datensätze findet.

		Parameters:
			$whereStatement - WHERE-Statement
	*/
	public function exists( $whereStatement, $integerResult=false ) {
		if( is_array($whereStatement) )
			$whereStatement = self::arrayToWhereCondition( $whereStatement );
		$result = $this->getOne('SELECT COUNT(*) FROM `' . $this->getTable() . '` WHERE ' . $whereStatement);
		if( !$integerResult ) {
			if($result[0] > 0)
				return true;
			return false;
		}
		return $result[0];
	}


	public function count( $condition=null ) {
		if( $condition === null )
			return $this->totalCount();
		if( is_array($condition) )
			$condition = self::buildAndCondition( $condition );
		elseif( !is_string($condition) )
			return null;
		$count = $this->getOne( 'SELECT COUNT(*) FROM `%TABLE` WHERE '. $condition );
		return intval( $count->get('COUNT(*)') );
	}


	public function totalCount() {
		$count = $this->getOne( 'SELECT COUNT(*) FROM `%TABLE`' );
		$count = $count->get('COUNT(*)');
		return intval( $count );
	}


	/*	Function: join
		Baut eine JOIN-Abfrage zusammen
	*/
/*
	public function join( $table, $thisTablesColumn, $otherColumn, $use_dbprefix=true ) {
		$table = ($use_dbprefix ? DBPREFIX : ''). $table;
		return 'JOIN `'. $table .'` ON `'. $this->getTable() .'`.`'. $thisTablesColumn .'` = `'. $table .'`.`'. $otherColumn .'`';
	}
*/


}