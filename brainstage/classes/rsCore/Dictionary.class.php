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
interface DictionaryInterface {

	function getLanguage();
	function getLanguageCode();
	function getTranslation( $key, $comment="" );

	function get( $key, $comment="" );
	function set( $key, $translation, $comment="" );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Dictionary implements DictionaryInterface {


	const ERROR_INVALID_LANGUAGE = "Invalid language object";


	protected static $_databaseTable = 'brainstage-translation';

	private $_DatabaseConnector;
	private $_Language;


	public function __construct( $languageCodeOrInstance ) {
		$this->_DatabaseConnector = Core::core()->database( static::$_databaseTable );
		if( $languageCodeOrInstance === null )
			$languageCodeOrInstance = Localization::getLanguage();
		$this->_Language = is_object( $languageCodeOrInstance ) ? $languageCodeOrInstance : \Brainstage\Language::getLanguageByShortCode( $languageCodeOrInstance );
		if( !is_object( $this->_Language ) )
			throw new Exception( self::ERROR_INVALID_LANGUAGE );
	}


	public function getLanguage() {
		return $this->_Language;
	}


	public function getLanguageCode() {
		return $this->_Language->shortCode;
	}


	public function getTranslation( $key, $comment="" ) {
		return \Brainstage\DictionaryTranslation::getTranslation( $this->getLanguage(), $key, $comment );
	}


	public function get( $key, $comment="" ) {
		return $this->getTranslation( $key, $comment );
	}


	public function set( $key, $translation, $comment="" ) {
		$Dataset = $this->getTranslation( $key, $comment );
		$Dataset->translation = $translation;
		$Dataset->adopt();
		return $Dataset;
	}


	public function __get( $key ) {
		return $this->get( $key );
	}


	public function __set( $key, $value ) {
		return $this->set( $key, $value );
	}


}