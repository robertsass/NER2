<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */


error_reporting( E_ALL ); ini_set( 'display_errors', '1' );
define( 'BASE_SCRIPT_FILE', __FILE__ );

require_once( 'config.php' );
require_once( 'brainstage/functions.php' );
require_once( 'brainstage/classes/Autoload.class.php' );


$Core = rsCore();
$Core->buildPage();
exit;


//------------------------------------------------------//


// EXAMPLE: common database functions
/*
$Database = $Core->database( 'brainstage-document-tree' );
$InsertedRow = $Database->insert( array('action' => 'fuckin') );
var_dump( $Database->count() );

$InsertedRow->duplicate();
var_dump( $Database->count() );

$LastRow = $Database->getLastRow();
$Database->deleteByPrimaryKey( $LastRow->id );
var_dump( $Database->count() );

$Database->getLastRow()->remove();
var_dump( $Database->count() );
exit;
*/




// EXAMPLE: output of NestedSets-Tree as JSON
/*
$Tree = $Core->databaseTree( 'brainstage-document-tree' );
echo $Tree;
exit;
*/




// EXAMPLE: How to do URL request recognition
/*
$ms = microtime(1);
$Request = $Core->getRequestPath();
$target = $Request->getRequestHandler()->getTarget();
$Document = Document::getById( $target, $language );
var_dump($Document->templateName);
exit;
*/




// EXAMPLE: using global functions to access superglobal variables like $_GET:
/*
var_dump( getVar('test'), postVar('test'), sessionVar('test', 'default value') );
exit;
*/



