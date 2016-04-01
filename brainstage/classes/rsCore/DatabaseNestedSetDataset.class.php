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
interface DatabaseNestedSetDatasetInterface {

	public function getLeftValue();
	public function getRightValue();
	public function getLevel();

	public function getParent();
	public function getParents();
	public function getChildren();
	public function getChildrenCount();
	public function hasChildren();
	public function isRoot();

	public function createBefore();
	public function createAfter();
	public function createChild();
	public function moveLeft();
	public function moveRight();
	public function moveUp();
	public function moveDown();

	public function remove( $keepChildren );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DatabaseNestedSetDataset extends DatabaseDataset implements DatabaseNestedSetDatasetInterface {


	private $_DatabaseNestedSet;


	protected function getNestedSet() {
		return $this->_DatabaseNestedSet;
	}


	public function __construct( $DatabaseNestedSet, $data ) {
		if( $DatabaseNestedSet instanceof DatabaseConnector )
			$DatabaseNestedSet = Core::core()->databaseTree( $DatabaseNestedSet->getTable() );
		elseif( !($DatabaseNestedSet instanceof DatabaseNestedSet) )
			throw new Exception("First argument must be of type DatabaseNestedSet");
		$this->_DatabaseNestedSet = $DatabaseNestedSet;
		parent::__construct( $DatabaseNestedSet->getDatabaseConnector(), $data );
	}


	public function duplicate() {
		throw new Exception( DatabaseNestedSet::ERROR_CANT_DUPLICATE_NODE );
	}


	protected function onChange() {}


	public function getColumns() {
		$columns = parent::getColumns();
		unset( $columns[ DatabaseNestedSet::FIELDNAME_LEFT ] );
		unset( $columns[ DatabaseNestedSet::FIELDNAME_RIGHT ] );
	#	unset( $columns[ DatabaseNestedSet::FIELDNAME_LEVEL ] );
		unset( $columns[ DatabaseNestedSet::FIELDNAME_MOVED ] );
		return $columns;
	}


	/* Getter */

	public function getLeftValue() {
		return $this->get( DatabaseNestedSet::FIELDNAME_LEFT );
	}


	public function getRightValue() {
		return $this->get( DatabaseNestedSet::FIELDNAME_RIGHT );
	}


	/**
	 * @todo Kann man das nicht auf $this->get( DatabaseNestedSet::FIELDNAME_LEVEL ) umstellen?
	 */
	public function getLevel() {
		return $this->getNestedSet()->getLevel( $this->getPrimaryKeyValue() );
	}


	public function getParent( $exceptions=true ) {
		return $this->getNestedSet()->getParent( $this->getPrimaryKeyValue(), $exceptions );
	}


	public function getParents() {
		$parents = array();
		$CurrentNode = $this;
		do {
			$Parent = $this->getNestedSet()->getParent( $CurrentNode->getPrimaryKeyValue(), false );
			if( $Parent )
				$parents[] = $Parent;
			$CurrentNode = $Parent;
		} while( $Parent !== null );
		return $parents;
	}


	public function getChildren() {
		return $this->getNestedSet()->getChildren( $this->getPrimaryKeyValue() );
	}


	public function getChildrenCount() {
		return $this->getNestedSet()->getChildrenCount( $this->getPrimaryKeyValue() );
	}


	public function hasChildren() {
		return $this->getNestedSet()->hasChildren( $this->getPrimaryKeyValue() );
	}


	public function isRoot() {
		return $this->getNestedSet()->getParent( $this->getPrimaryKeyValue(), false ) === null;
	}


	/* Creation methods */

	public function createBefore() {
		return $this->getNestedSet()->createBefore( $this->getPrimaryKeyValue() );
	}

	public function createAfter() {
		return $this->getNestedSet()->createAfter( $this->getPrimaryKeyValue() );
	}

	public function createChild() {
		return $this->getNestedSet()->createChild( $this->getPrimaryKeyValue() );
	}


	/* Reordering methods */

	public function moveLeft() {
		return $this->getNestedSet()->moveLeft( $this->getPrimaryKeyValue() );
	}

	public function moveRight() {
		return $this->getNestedSet()->moveRight( $this->getPrimaryKeyValue() );
	}

	public function moveUp() {
		return $this->getNestedSet()->moveUp( $this->getPrimaryKeyValue() );
	}

	public function moveDown() {
		return $this->getNestedSet()->moveDown( $this->getPrimaryKeyValue() );
	}


	/* Deletion methods */

	public function remove( $keepChildren=true ) {
		return $this->getNestedSet()->remove( $this->getPrimaryKeyValue(), $keepChildren );
	}


}