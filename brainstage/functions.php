<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */


function rsCore() {
	return \rsCore\Core::core();
}


function getVar( $key, $default=null ) {
	$Core = \rsCore\Core::core();
	return \rsCore\Core::functions()->arraysValueForKey( $Core->getGlobalVariable( 'GET' ), $key, $default );
}


function postVar( $key, $default=null ) {
	$Core = \rsCore\Core::core();
	return \rsCore\Core::functions()->arraysValueForKey( $Core->getGlobalVariable( 'POST' ), $key, $default );
}


function sessionVar( $key, $default=null ) {
	return \rsCore\Core::functions()->arraysValueForKey( $_SESSION, $key, $default );
}


function serverVar( $key, $default=null ) {
	$Core = \rsCore\Core::core();
	return \rsCore\Core::functions()->arraysValueForKey( $Core->getGlobalVariable( 'SERVER' ), $key, $default );
}


function valueByKey( array $array, $key, $default=null ) {
	return \rsCore\Core::functions()->arraysValueForKey( $array, $key, $default );
}


function database( $table ) {
	return \rsCore\Core::database( $table );
}


function requestPath() {
	return \rsCore\Core::getRequestPath();
}


function staticResourceUrl( $path ) {
	return \rsCore\Core::functions()->rewriteResourceUrl( $path );
}


function useragent() {
	return \rsCore\Core::getUseragent();
}


function useragentLanguages() {
	return \rsCore\Useragent::detectLanguages();
}


function languageCode() {
	return \rsCOre\Localization::getLanguage();
}


function translate( $key, $comment=null ) {
	return \rsCore\Core::core()->translate( $key, $comment );
}


function t( $key, $comment=null ) {
	return translate( $key, $comment );
}


function redirect( $targetUrl ) {
	\rsCore\Core::functions()->redirect( $targetUrl );
}


function user() {
	return \rsCore\Auth::getUser();
}


function isLoggedin() {
	return \rsCore\Auth::isLoggedin();
}


function logException( \Exception $Exception ) {
	return \Brainstage\ExceptionLog::add( $Exception );
}