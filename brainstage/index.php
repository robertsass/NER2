<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */


error_reporting( E_ALL ); ini_set( 'display_errors', '1' );
define( 'BASE_SCRIPT_FILE', __FILE__ );

require_once( '../config.php' );
require_once( 'functions.php' );
require_once( 'classes/Autoload.class.php' );


$Core = rsCore();
#$Core->activateErrorHandler();

$Brainstage = new \Brainstage\Brainstage();
$Brainstage->build();
