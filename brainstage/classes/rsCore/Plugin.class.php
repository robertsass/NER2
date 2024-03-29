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
interface PluginInterface {

	static function templateRegistration( FrameworkInterface $Framework );
	static function brainstageRegistration( FrameworkInterface $Framework );
	static function apiRegistration( FrameworkInterface $Framework );
	static function registerPrivileges();

	static function instance( FrameworkInterface $Framework );
	static function identifier( $fullIdentifier );
	function getIdentifier( $fullIdentifier );

	static function translate( $string, $comment="" );
	static function t( $translationString, $comment="" );

	function getBooleanSetting( $key, $createIfDoesNotExist=false );
	function getMixedSetting( $key, $createIfDoesNotExist=false );
	function getTextSetting( $key, $createIfDoesNotExist=false );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Plugin extends CoreClass implements PluginInterface {


	private $_Framework;
	private $_identifier;


	/** Wird vom Frontend-Template aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param Framework $Framework
	 */
	public static function templateRegistration( FrameworkInterface $Framework ) {
	}


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param Framework $Framework
	 */
	public static function brainstageRegistration( FrameworkInterface $Framework ) {
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param Framework $Framework
	 */
	public static function apiRegistration( FrameworkInterface $Framework ) {
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
	}


	/** Instanziiert das Plugin
	 * @param Framework $Framework
	 * @return Plugin
	 * @final
	 */
	final public static function instance( FrameworkInterface $Framework=null ) {
		return new static( $Framework );
	}


	/** Gibt den Identifier des Plugins (dessen Namen) zurück
	 * @param boolean $fullIdentifier Bei true wird der Plugin-Name inklusive des Namespace zurückgegeben
	 * @return string
	 * @final
	 */
	final public static function identifier( $fullIdentifier=true ) {
		$classname = explode( '\\', get_class( new static(null, false) ) );
		if( $fullIdentifier )
			return join( '/', $classname );
		return array_pop( $classname );
	}


	/** Gibt den Identifier des Plugins (dessen Namen) zurück
	 * @param boolean $fullIdentifier Bei true wird der Plugin-Name inklusive des Namespace zurückgegeben
	 * @return string
	 * @final
	 */
	final public function getIdentifier( $fullIdentifier=true ) {
		if( !$this->_identifier ) {
			$classname = explode( '\\', get_class( $this ) );
			$this->_identifier = $classname;
		}
		if( $fullIdentifier )
			return join( '/', $this->_identifier );
		$classname = $this->_identifier;
		return array_pop( $classname );
	}


	/** Übersetzt einen String in die gerade verwendete Sprache
	 * @param string $string Übersetzungsschlüssel
	 * @param string $comment
	 * @return string
	 */
	public static function translate( $string, $comment="" ) {
		return \rsCore\Core::core()->translate( $string, $comment );
	}


	/** Übersetzt einen String in die gerade verwendete Sprache; Alias zu translate()
	 * @param string $string Übersetzungsschlüssel
	 * @param string $comment
	 * @return string
	 */
	public static function t( $translationString, $comment="" ) {
		return static::translate( $translationString, $comment );
	}


	/** Gibt einen Setting-Datensatz anhand eines Plugin-gebundenen Schlüssels zurück
	 * @param string $key Schlüssel
	 * @param boolean $createIfDoesNotExist
	 * @return \Brainstage\Setting
	 */
	public function getBooleanSetting( $key, $createIfDoesNotExist=false ) {
		$key = $this->getIdentifier() .'/'. $key;
		return \Brainstage\Setting::getBooleanSetting( $key, $createIfDoesNotExist );
	}


	/** Gibt einen Setting-Datensatz anhand eines Plugin-gebundenen Schlüssels zurück
	 * @param string $key Schlüssel
	 * @param boolean $createIfDoesNotExist
	 * @return \Brainstage\Setting
	 */
	public function getMixedSetting( $key, $createIfDoesNotExist=false ) {
		$key = $this->getIdentifier() .'/'. $key;
		return \Brainstage\Setting::getMixedSetting( $key, $createIfDoesNotExist );
	}


	/** Gibt einen Setting-Datensatz anhand eines Plugin-gebundenen Schlüssels zurück
	 * @param string $key Schlüssel
	 * @param boolean $createIfDoesNotExist
	 * @return \Brainstage\Setting
	 */
	public function getTextSetting( $key, $createIfDoesNotExist=false ) {
		$key = $this->getIdentifier() .'/'. $key;
		return \Brainstage\Setting::getTextSetting( $key, $createIfDoesNotExist );
	}


	/** Gibt ein Plugin-spezifisches Privileg des Nutzers zurück
	 * @param string $key
	 * @param boolean $includeGroupRights
	 * @return \Brainstage\UserRight
	 */
	protected static function getUserRight( $key, $includeGroupRights=true ) {
		if( !isLoggedin() )
			return null;
		return user()->getPluginSpecificRight( static::identifier(true), $key, $includeGroupRights );
	}


	/** Prüft ob der Nutzer ein oder mehrere (kommaseparierte) Plugin-spezifische Privilegien besitzt
	 * @param string $rights
	 * @return boolean
	 */
	protected static function may( $rights, $comparisonValue=true, $includeGroupRights=true ) {
		if( !isLoggedin() )
			return false;
		$User = \rsCore\Auth::getUser();
		if( $User->isSuperAdmin() )
			return true;
		foreach( explode( ',', $rights ) as $right ) {
			$right = trim( $right );
			$pluginRights = $User->getPluginSpecificRight( static::identifier(true), $right, true );
			if( empty( $pluginRights ) )
				return false;
			foreach( $pluginRights as $Right ) {
				if( !$Right || $Right->value != $comparisonValue )
					return false;
			}
		}
		return true;
	}


	/** Wirft eine Exception, wenn kein authorisierter Nutzer eingeloggt ist
	 * @param string $message
	 */
	protected static function throwExceptionIfNotAuthorized( $message=null ) {
		if( !isLoggedin() )
			throw new \rsCore\NotAuthorizedException( $message );
	}


	/** Wirft eine Exception, wenn der Nutzer nicht über die Rechte verfügt
	 * @param mixed $rights
	 * @param string $message
	 */
	protected static function throwExceptionIfNotPrivileged( $rights, $message=null ) {
		static::throwExceptionIfNotAuthorized();
		if( !user()->isSuperAdmin() ) {
			if( !is_array( $rights ) ) {
				$splittedRights = explode( ',', $rights );
				$rights = array();
				foreach( $splittedRights as $right )
					$rights[] = trim( $right );
			}
			foreach( $rights as $right ) {
				if( !static::may( $right ) )
					throw new \rsCore\NotPrivilegedException( $message );
			}
		}
	}


	/** Gibt das Framework zurück
	 * @return Framework
	 * @final
	 */
	final protected function getFramework() {
		return $this->_Framework;
	}


	/** Registriert einen Hook im Framework
	 * @final
	 * @return object Selbstreferenz
	 */
	final protected function hook( $event, $method=null ) {
		if( $this->getFramework() )
			$this->getFramework()->registerHook( $this, $event, $method );
		return $this;
	}


	/** Entfernt einen Hook im Framework
	 * @final
	 * @return object Selbstreferenz
	 */
	final protected function unhook( $event ) {
		if( $this->getFramework() )
			$this->getFramework()->unregisterHook( $this, $event );
		return $this;
	}


	/** Ruft im Framework die Hooks eines Events auf
	 * @final
	 * @return void
	 */
	final protected function callHooks( $event, $params ) {
		if( $this->getFramework() )
			return $this->getFramework()->callHooks( $event, $params );
		return null;
	}


	/** Konstruktor
	 * @final
	 */
	final private function __construct( FrameworkInterface $Framework=null, $init=true ) {
		if( $Framework )
			$this->_Framework = $Framework;
		if( $init )
			$this->init();
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


}