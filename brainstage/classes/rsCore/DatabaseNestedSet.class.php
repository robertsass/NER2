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
interface DatabaseNestedSetInterface {

	public static function instance( $table, $usePrefix, $host, $user, $pass, $database );

	public function createRoot();
	public function createChild( $parentNodeInstanceOrId );
	public function createBefore( $nodeInstanceOrId );
	public function createAfter( $nodeInstanceOrId );
	public function createLeft( $nodeInstanceOrId );
	public function createRight( $nodeInstanceOrId );
	public function remove( $nodeInstanceOrId, $keepChildren );
	public function moveLeft( $nodeInstanceOrId );
	public function moveRight( $nodeInstanceOrId );
	public function moveUp( $nodeInstanceOrId );
	public function moveDown( $nodeInstanceOrId );

	public function getByPrimaryKey( $key );
	public function getNodeById( $nodeId );
	public function getNodeByLeftValue( $leftValue, $exceptions );
	public function getNodeByRightValue( $rightValue, $exceptions );
	public function getAll( $whereStatement );

	public function getLevel( $nodeInstanceOrId );
	public function getRoot();
	public function getParent( $nodeInstanceOrId, $exceptions );
	public function getChildren( $nodeInstanceOrId, $exceptions );
	public function getChildrenCount( $nodeInstanceOrId, $exceptions );
	public function getChildrenByColumns( $columns, $nodeInstanceOrId, $exceptions );
	public function hasChildren( $nodeInstanceOrId, $exceptions );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DatabaseNestedSet extends CoreClass implements DatabaseNestedSetInterface, DatabaseHandlerFactoryInterface {


	const FIELDNAME_LEFT		= 'leftValue';
	const FIELDNAME_RIGHT	= 'rightValue';
	const FIELDNAME_LEVEL	= 'nodeLevel';
	const FIELDNAME_MOVED	= 'nodeMoved';

	const ERROR_ROOT_ALREADY_EXISTS = "Can't create new root node because there already exists one.";
	const ERROR_ROOT_HAS_NO_PARENTS = "Root node has no parents.";
	const ERROR_INVALID_PARAMETER_GIVEN_AS_NODE = "Invalid parameter given as node. Expected DatabaseDataset instance or integer primary key.";
	const ERROR_INVALID_LEVEL_TO_CREATE_BROTHER_NODE = "Can't create brother node on this level.";
	const ERROR_CANT_DELETE_ROOT = "Root can't be deleted.";
	const ERROR_CANT_MOVE_NODE = "Can't move node any further.";
	const ERROR_NODE_NOT_FOUND = "No such node found.";


	private $_DatabaseConnector;
	private static $_instances;


	/* Factory */

	public static function instance( $table, $usePrefix=true, $host=null, $user=null, $pass=null, $database=null ) {
		if( static::$_instances === null )
			static::$_instances = array();
		$tablename = ($usePrefix ? DBPREFIX : ''). $table;
		$instanceKey = $user .'@'. $host .'/'. $database .':'. $tablename;
		if( !array_key_exists( $instanceKey, static::$_instances ) )
			static::$_instances[ $instanceKey ] = new static( $table, $usePrefix, $host, $user, $pass, $database );
		return static::$_instances[ $instanceKey ];
	}


	/* Getter */

	public function getDatabaseConnector() {
		return $this->_DatabaseConnector;
	}


	protected function myTable() {
		return $this->getDatabaseConnector()->getTable();
	}


	protected function myPrimaryKey() {
		return $this->getDatabaseConnector()->getPrimaryKey();
	}


	/* Constructor */

	public function __construct( $table, $usePrefix=true, $host=null, $user=null, $pass=null, $database=null ) {
		$this->_DatabaseConnector = Database::connect( $table, $usePrefix, $host, $user, $pass, $database );
		$this->_DatabaseConnector->registerHandlerParent( $this );
		Core::core()->registerDatabaseDatasetHandler( $this->_DatabaseConnector->getTable(), '\rsCore\DatabaseNestedSetDataset' );
	}


	public function __destruct() {
		$this->unlockTable();
	}


	public function __toString() {
		return json_encode( $this->constructTree() );
	}


	/* Internal helper functions */

	protected function execute( $sql ) {
		$this->getDatabaseConnector()->execute( $sql );
	}


	protected function get( $sql ) {
		return $this->getDatabaseConnector()->getArray( $sql );
	}


	protected function insert( array $keysAndValues ) {
		return $this->getDatabaseConnector()->insert( $keysAndValues );
	}


	protected function lockTable() {
		$this->execute( 'LOCK TABLES `%TABLE` WRITE, `%TABLE` AS tree1 WRITE, `%TABLE` AS tree2 WRITE' );
	}


	protected function unlockTable() {
		$this->execute( 'UNLOCK TABLES' );
	}


	protected function getNodeByParameter( $nodeInstanceOrId ) {
		if( $nodeInstanceOrId instanceof DatabaseNestedSetDataset )
			return $nodeInstanceOrId;
		elseif( is_object( $nodeInstanceOrId ) )
			return $this->getByPrimaryKey( $nodeInstanceOrId->getPrimaryKeyValue() );
		elseif( is_int( $nodeInstanceOrId ) || intval( $nodeInstanceOrId ) > 0 )
			return $this->getByPrimaryKey( intval( $nodeInstanceOrId ) );
		else
			throw new Exception( self::ERROR_INVALID_PARAMETER_GIVEN_AS_NODE );
	}


	public function constructTree( DatabaseNestedSetDataset $Node=null ) {
		if( $Node === null )
			$Node = $this->getRoot(false);

		if( $Node !== null ) {
			$columns = $Node->getColumns();

			$columns['children'] = array();
			if( $Node->hasChildren() )
				foreach( $Node->getChildren() as $Child )
					$columns['children'][] = $this->constructTree( $Child );
		}
		else
			$columns = array();

		return $columns;
	}


	public function getByPrimaryKey( $key ) {
		if( is_int( $key ) && $key > 0 )
			return $this->getDatabaseConnector()->getByPrimaryKey( $key );
		return null;
	}


	public function getNodeById( $nodeId, $exceptions=true ) {
		$Node = $this->getByPrimaryKey( intval( $nodeId ) );
		if( $exceptions && !$Node )
			throw new Exception( self::ERROR_NODE_NOT_FOUND );
		return $Node;
	}


	public function getNodeByLeftValue( $leftValue, $exceptions=true ) {
		$Node = $this->getDatabaseConnector()->getByColumn( self::FIELDNAME_LEFT, $leftValue );
		if( $exceptions && !$Node )
			throw new Exception( self::ERROR_NODE_NOT_FOUND );
		return $Node;
	}


	public function getNodeByRightValue( $rightValue, $exceptions=true ) {
		$Node = $this->getDatabaseConnector()->getByColumn( self::FIELDNAME_RIGHT, $rightValue );
		if( $exceptions && !$Node )
			throw new Exception( self::ERROR_NODE_NOT_FOUND );
		return $Node;
	}


	public function getByColumn( $column, $value, $allowMultipleResults=false, $sorting=null, $limit=null, $start=null ) {
		return $this->getDatabaseConnector()->getByColumn( $column, $value, $allowMultipleResults, $sorting, $limit, $start );
	}


	public function getByColumns( $columns, $allowMultipleResults=false, $sorting=null, $limit=null, $start=null ) {
		return $this->getDatabaseConnector()->getByColumns( $columns, $allowMultipleResults, $sorting, $limit, $start );
	}


	public function getAll( $whereStatement=null ) {
		return $this->getDatabaseConnector()->getAll( $whereStatement );
	}


	public function getLevel( $nodeInstanceOrId ) {
		if( $nodeInstanceOrId instanceof DatabaseDataset )
			$nodeId = $nodeInstanceOrId->getPrimaryKeyValue();
		else
			$nodeId = intval( $nodeInstanceOrId );
		$sql = 'SELECT tree2.`'. $this->myPrimaryKey() .'` AS id, COUNT(*) AS level ';
		$sql .= 'FROM `%TABLE` AS tree1, `%TABLE` AS tree2 ';
		$sql .= 'WHERE tree2.`'. self::FIELDNAME_LEFT .'` BETWEEN tree1.`'. self::FIELDNAME_LEFT .'` AND tree1.`'. self::FIELDNAME_RIGHT .'` ';
		$sql .= 'GROUP BY tree2.`'. self::FIELDNAME_LEFT .'` ';
		$sql .= 'ORDER BY ABS(CAST(tree2.`'. $this->myPrimaryKey() .'` AS SIGNED) - '. $nodeId .') ';
		$result = $this->getDatabaseConnector()->getOne( $sql );
		if( $result->id == $nodeId )
			return intval( $result->level ) -1;
		return null;
	}


	public function getRoot( $exceptions=true ) {
		$Node = $this->getDatabaseConnector()->getByColumn( self::FIELDNAME_LEVEL, 0, false, array('id' => 'ASC') );
		if( $exceptions && !$Node )
			throw new Exception( self::ERROR_NODE_NOT_FOUND );
		return $Node;
	}


	public function getParent( $nodeInstanceOrId, $exceptions=true ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		if( $Node->getLeftValue() > 0 ) {
			$condition = '`'. self::FIELDNAME_LEFT .'` < '. $Node->getLeftValue();
			$condition .= ' AND `'. self::FIELDNAME_RIGHT .'` > '. $Node->getRightValue();
			$condition .= ' ORDER BY `'. self::FIELDNAME_LEFT .'` DESC';
			return $this->getDatabaseConnector()->getRow( $condition );
		}
		elseif( $exceptions )
			throw new Exception( self::ERROR_ROOT_HAS_NO_PARENTS );
		return null;
	}


	public function getChildren( $nodeInstanceOrId, $exceptions=true ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		$condition = '`'. self::FIELDNAME_LEFT .'` > '. $Node->getLeftValue();
		$condition .= ' AND `'. self::FIELDNAME_RIGHT .'` < '. $Node->getRightValue();
		$condition .= ' AND `'. self::FIELDNAME_LEVEL .'` = '. ($this->getLevel( $Node ) +1);
		return $this->getDatabaseConnector()->getAll( $condition, 'ORDER BY `'. self::FIELDNAME_RIGHT .'` ASC' );
	}


	public function getChildrenByColumns( $nodeInstanceOrId, $columns, $exceptions=true ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		$condition = '`'. self::FIELDNAME_LEFT .'` > '. $Node->getLeftValue();
		$condition .= ' AND `'. self::FIELDNAME_RIGHT .'` < '. $Node->getRightValue();
		$condition .= ' AND `'. self::FIELDNAME_LEVEL .'` = '. ($this->getLevel( $Node ) +1);
		$condition .= ' AND '. DatabaseConnector::buildAndCondition( $columns );
		return $this->getDatabaseConnector()->getAll( $condition, 'ORDER BY `'. self::FIELDNAME_RIGHT .'` ASC' );
	}


	public function getChildrenCount( $nodeInstanceOrId, $exceptions=true ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		$condition = '`'. self::FIELDNAME_LEFT .'` > '. $Node->getLeftValue();
		$condition .= ' AND `'. self::FIELDNAME_RIGHT .'` < '. $Node->getRightValue();
		$condition .= ' AND `'. self::FIELDNAME_LEVEL .'` = '. ($this->getLevel( $Node ) +1);
		return $this->getDatabaseConnector()->count( $condition );
	}


	public function hasChildren( $nodeInstanceOrId, $exceptions=true ) {
		return $this->getChildrenCount( $nodeInstanceOrId, $exceptions ) > 0;
	}


	/* DatabaseHandlerFactoryInterface methods */

	public function getHandlerInstance( CoreFrameworkHandlerFactory $HandlerFactory, $data ) {
		return $HandlerFactory->getHandlerInstance( $this, $data );
	}


	/* Public methods */

	public function createRoot() {
		return $this->_createRoot( true );
	}


	private function _createRoot( $externalCall=false ) {
		if( $this->getDatabaseConnector()->count() > 0 )
			if( $externalCall )
				throw new Exception( self::ERROR_ROOT_ALREADY_EXISTS );
			else
				return false;
		else {
			$Dataset = $this->insert(array(
				self::FIELDNAME_LEFT => 0,
				self::FIELDNAME_RIGHT => 1,
				self::FIELDNAME_LEVEL => 0
			));
			if( $Dataset === null ) {
				$error = $this->getDatabaseConnector()->popError();
				throw new Exception( $error['text'] );
			}
			return $Dataset;
		}
	}


	public function createChild( $parentNodeInstanceOrId ) {
		return $this->_createChild( $parentNodeInstanceOrId, true );
	}


	private function _createChild( $parentNodeInstanceOrId, $externalCall=false ) {
		$Parent = $this->getNodeByParameter( $parentNodeInstanceOrId );

		$parentsRightValue = $Parent->getRightValue();
		$parentsLevel = $this->getLevel( $Parent );

		$leftValueUpdateQuery = 'UPDATE `%TABLE` ';
		$leftValueUpdateQuery .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` + 2 ';
		$leftValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_LEFT .'` >= '. $parentsRightValue;

		$rightValueUpdateQuery = 'UPDATE `%TABLE` ';
		$rightValueUpdateQuery .= 'SET `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + 2 ';
		$rightValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_RIGHT .'` >= '. $parentsRightValue;

		$this->lockTable();
		$this->execute( $leftValueUpdateQuery );
		$this->execute( $rightValueUpdateQuery );
		$Child = $this->insert(array(
			self::FIELDNAME_LEFT => $parentsRightValue,
			self::FIELDNAME_RIGHT => $parentsRightValue+1,
			self::FIELDNAME_LEVEL => $parentsLevel +1
		));
		$this->unlockTable();

		if( $Child === null ) {
			$error = $this->getDatabaseConnector()->popError();
			if( $externalCall )
				throw new Exception( $error['text'] );
			else
				return false;
		}
		return $Child;
	}


	public function createBefore( $nodeInstanceOrId ) {
		return $this->_createBefore( $nodeInstanceOrId, true );
	}


	private function _createBefore( $nodeInstanceOrId, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		$nodesLevel = $this->getLevel( $Node );
		if( $nodesLevel < 1 )
			if( $externalCall )
				throw new Exception( self::ERROR_INVALID_LEVEL_TO_CREATE_BROTHER_NODE );
			else
				return false;

		$nodesLeftValue = $Node->getLeftValue();

		$leftValueUpdateQuery = 'UPDATE `%TABLE` ';
		$leftValueUpdateQuery .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` + 2 ';
		$leftValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_LEFT .'` >= '. $nodesLeftValue;

		$rightValueUpdateQuery = 'UPDATE `%TABLE` ';
		$rightValueUpdateQuery .= 'SET `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + 2 ';
		$rightValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_RIGHT .'` >= '. $nodesLeftValue;

		$this->lockTable();
		$this->execute( $leftValueUpdateQuery );
		$this->execute( $rightValueUpdateQuery );
		$Brother = $this->insert(array(
			self::FIELDNAME_LEFT => $nodesLeftValue,
			self::FIELDNAME_RIGHT => $nodesLeftValue +1,
			self::FIELDNAME_LEVEL => $nodesLevel
		));
		$this->unlockTable();

		return $Brother;
	}


	public function createAfter( $nodeInstanceOrId ) {
		return $this->_createAfter( $nodeInstanceOrId, true );
	}


	private function _createAfter( $nodeInstanceOrId, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		$nodesLevel = $this->getLevel( $Node );
		if( $nodesLevel < 1 )
			if( $externalCall )
				throw new Exception( self::ERROR_INVALID_LEVEL_TO_CREATE_BROTHER_NODE );
			else
				return false;

		$nodesRightValue = $Node->getRightValue();

		$leftValueUpdateQuery = 'UPDATE `%TABLE` ';
		$leftValueUpdateQuery .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` + 2 ';
		$leftValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_LEFT .'` >= '. ($nodesRightValue+1);

		$rightValueUpdateQuery = 'UPDATE `%TABLE` ';
		$rightValueUpdateQuery .= 'SET `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + 2 ';
		$rightValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_RIGHT .'` >= '. ($nodesRightValue +1);

		$this->lockTable();
		$this->execute( $leftValueUpdateQuery );
		$this->execute( $rightValueUpdateQuery );
		$Brother = $this->insert(array(
			self::FIELDNAME_LEFT => $nodesRightValue +1,
			self::FIELDNAME_RIGHT => $nodesLeftValue +2,
			self::FIELDNAME_LEVEL => $nodesLevel
		));
		$this->unlockTable();

		return $Brother;
	}


	public function createLeft( $nodeInstanceOrId ) {
		return $this->createBefore( $nodeInstanceOrId );
	}


	public function createRight( $nodeInstanceOrId ) {
		return $this->createAfter( $nodeInstanceOrId );
	}


	public function remove( $nodeInstanceOrId, $keepChildren ) {
		return $this->_remove( $nodeInstanceOrId, $keepChildren, true );
	}


	private function _remove( $nodeInstanceOrId, $keepChildren, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		if( $this->getLevel( $Node ) < 1 )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_DELETE_ROOT );
			else
				return false;

		$leftValue = $Node->getLeftValue();
		$rightValue = $Node->getRightValue();

		if( $keepChildren ) {
			$childrenUpdateQuery = 'UPDATE `%TABLE` ';
			$childrenUpdateQuery .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` -1 ';
			$childrenUpdateQuery .= ', `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` -1, ';
			$childrenUpdateQuery .= ', `'. self::FIELDNAME_LEVEL .'` = `'. self::FIELDNAME_LEVEL .'` -1, ';
			$childrenUpdateQuery .= 'WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $leftValue .' AND '. $rightValue;
		}

		$leftValueUpdateQuery = 'UPDATE `%TABLE` ';
		$leftValueUpdateQuery .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` - 2 ';
		$leftValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_LEFT .'` > '. $rightValue;

		$rightValueUpdateQuery = 'UPDATE `%TABLE` ';
		$rightValueUpdateQuery .= 'SET `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` - 2 ';
		$rightValueUpdateQuery .= 'WHERE `'. self::FIELDNAME_RIGHT .'` > '. $leftValue;

		$this->lockTable();
		if( $keepChildren )
			$this->execute( $childrenUpdateQuery );
		$this->execute( $leftValueUpdateQuery );
		$this->execute( $rightValueUpdateQuery );
		$this->getDatabaseConnector()->delete( $Node->getWhereCondition() );
		$this->unlockTable();

		return true;
	}


	public function moveLeft( $nodeInstanceOrId ) {
		return $this->_moveLeft( $nodeInstanceOrId, true );
	}


	private function _moveLeft( $nodeInstanceOrId, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		if( $this->getLevel( $Node ) < 1 )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;
		$LefterNode = $this->getNodeByRightValue( $Node->getLeftValue() -1, false );
		if( $LefterNode === null )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;

		$leftDifference = $Node->getLeftValue() - $LefterNode->getLeftValue();
		$rightDifference = $Node->getRightValue() - $LefterNode->getRightValue();

		$resetMovedFlag = 'UPDATE `%TABLE` SET `'. self::FIELDNAME_MOVED .'` = 0';

		$moveRight = 'UPDATE `%TABLE` ';
		$moveRight .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` + '. $rightDifference .', ';
		$moveRight .= '`'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + '. $rightDifference .', ';
		$moveRight .= '`'. self::FIELDNAME_MOVED .'` = 1 ';
		$moveRight .= 'WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $LefterNode->getLeftValue()
					.' AND '. $LefterNode->getRightValue();

		$moveLeft = 'UPDATE `%TABLE` ';
		$moveLeft .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` - '. $leftDifference .', ';
		$moveLeft .= '`'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` - '. $leftDifference .' ';
		$moveLeft .= 'WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $Node->getLeftValue()
					.' AND '. $Node->getRightValue() .' AND `'. self::FIELDNAME_MOVED .'` = 0';

		$this->lockTable();
		$this->execute( $resetMovedFlag );
		$this->execute( $moveRight );
		$this->execute( $moveLeft );
		$this->execute( $resetMovedFlag );
		$this->unlockTable();

		return $this->getNodeById( $Node->getPrimaryKeyValue() );
	}


	public function moveRight( $nodeInstanceOrId ) {
		return $this->_moveRight( $nodeInstanceOrId, true );
	}


	private function _moveRight( $nodeInstanceOrId, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		if( $this->getLevel( $Node ) < 1 )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;
		$RighterNode = $this->getNodeByLeftValue( $Node->getRightValue() +1, false );
		if( $RighterNode === null )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;

		$leftDifference = $RighterNode->getLeftValue() - $Node->getLeftValue();
		$rightDifference = $RighterNode->getRightValue() - $Node->getRightValue();

		$resetMovedFlag = 'UPDATE `%TABLE` SET `'. self::FIELDNAME_MOVED .'` = 0';

		$moveLeft = 'UPDATE `%TABLE` ';
		$moveLeft .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` - '. $leftDifference .', ';
		$moveLeft .= '`'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` - '. $leftDifference .', ';
		$moveLeft .= '`'. self::FIELDNAME_MOVED .'` = 1 ';
		$moveLeft .= 'WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $RighterNode->getLeftValue()
					.' AND '. $RighterNode->getRightValue();

		$moveRight = 'UPDATE `%TABLE` ';
		$moveRight .= 'SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` + '. $rightDifference .', ';
		$moveRight .= '`'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + '. $rightDifference .' ';
		$moveRight .= 'WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $Node->getLeftValue()
					.' AND '. $Node->getRightValue() .' AND `'. self::FIELDNAME_MOVED .'` = 0';

		$this->lockTable();
		$this->execute( $resetMovedFlag );
		$this->execute( $moveLeft );
		$this->execute( $moveRight );
		$this->execute( $resetMovedFlag );
		$this->unlockTable();

		return $this->getNodeById( $Node->getPrimaryKeyValue() );
	}


	public function moveUp( $nodeInstanceOrId ) {
		return $this->_moveUp( $nodeInstanceOrId, true );
	}


	private function _moveUp( $nodeInstanceOrId, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		if( $this->getLevel( $Node ) < 2 )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;

		do {
			$moved = $this->_moveRight( $Node );
		} while( $moved !== false );

		$Parent = $Node->getParent( false );
		if( $Parent === null )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;

		$nodesWidth = $Node->getRightValue() - $Node->getLeftValue() +1;

		$moveOut = 'UPDATE `%TABLE`';
		$moveOut .= ' SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` + 1';
		$moveOut .= ', `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + 1';
		$moveOut .= ', `'. self::FIELDNAME_LEVEL .'` = `'. self::FIELDNAME_LEVEL .'` - 1';
		$moveOut .= ' WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $Node->getLeftValue() .' AND '. $Node->getRightValue();

		$resizeParent = 'UPDATE `%TABLE`';
		$resizeParent .= ' SET `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` - '. $nodesWidth;
		$resizeParent .= ' WHERE `'. $this->getDatabaseConnector()->getPrimaryKey() .'` = '. $Parent->getPrimaryKeyValue();

		$this->lockTable();
		$this->execute( $moveOut );
		$this->execute( $resizeParent );
		$this->unlockTable();

		return $this->getNodeById( $Node->getPrimaryKeyValue() );
	}


	public function moveDown( $nodeInstanceOrId ) {
		return $this->_moveDown( $nodeInstanceOrId, true );
	}


	private function _moveDown( $nodeInstanceOrId, $externalCall=false ) {
		$Node = $this->getNodeByParameter( $nodeInstanceOrId );
		if( $this->getLevel( $Node ) < 1 )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;

		do {
			$moved = $this->_moveRight( $Node );
		} while( $moved !== false );

		$LeftBrother = $this->getNodeByRightValue( $Node->getLeftValue() -1, false );
		if( $LeftBrother === null )
			if( $externalCall )
				throw new Exception( self::ERROR_CANT_MOVE_NODE );
			else
				return false;

		$nodesWidth = $Node->getRightValue() - $Node->getLeftValue() +1;

		$moveIn = 'UPDATE `%TABLE`';
		$moveIn .= ' SET `'. self::FIELDNAME_LEFT .'` = `'. self::FIELDNAME_LEFT .'` - 1';
		$moveIn .= ', `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` - 1';
		$moveIn .= ', `'. self::FIELDNAME_LEVEL .'` = `'. self::FIELDNAME_LEVEL .'` + 1';
		$moveIn .= ' WHERE `'. self::FIELDNAME_LEFT .'` BETWEEN '. $Node->getLeftValue() .' AND '. $Node->getRightValue();

		$resizeParent = 'UPDATE `%TABLE`';
		$resizeParent .= ' SET `'. self::FIELDNAME_RIGHT .'` = `'. self::FIELDNAME_RIGHT .'` + '. $nodesWidth;
		$resizeParent .= ' WHERE `'. $this->getDatabaseConnector()->getPrimaryKey() .'` = '. $LeftBrother->getPrimaryKeyValue();

		$this->lockTable();
		$this->execute( $moveIn );
		$this->execute( $resizeParent );
		$this->unlockTable();

		return $this->getNodeById( $Node->getPrimaryKeyValue() );
	}


}