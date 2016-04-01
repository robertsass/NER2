<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface DictionaryTranslationInterface {

	static function addTranslation( $languageCodeOrInstance, $key, $translation, $comment="" );

	static function getTranslationKeys();
	static function getTranslationKeysNotDefinedInLanguage( $languageCodeOrInstance );
	static function getTranslations( $limit, $start );
	static function getTranslationsByKey( $key );
	static function getTranslationsByLanguage( $languageCodeOrInstance, $limit, $start );
	static function getTranslation( $languageCodeOrInstance, $key, $comment="", $createIfDoesNotExist=true );

	static function countTranslations();
	static function countTranslationsByLanguage( $languageCodeOrInstance );
	static function countTranslationKeys();

	function getTranslatedString();

	function remove();

	function __toString();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DictionaryTranslation extends \rsCore\DatabaseDatasetAbstract implements DictionaryTranslationInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-translation';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Fügt eine Übersetzung hinzu
	 * @param mixed $languageCodeOrInstance
	 * @param string $key Übersetzungsschlüssel
	 * @param string $translation Übersetzung
	 * @return DictionaryTranslation
	 * @api
	 */
	public static function addTranslation( $languageCodeOrInstance, $key, $translation, $comment="" ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );

		$Translation = self::getByColumns( array('key' => $key, 'comment' => $comment, 'language' => $Language->shortCode) );
		if( $Translation !== null )
			return $Translation;

		$Translation = self::create();
		if( $Translation ) {
			$Translation->language = $Language->shortCode;
			$Translation->key = $key;
			$Translation->comment = $comment;
			$Translation->translation = $translation;
			$Translation->adopt();
		}
		return $Translation;
	}


	/** Gibt alle Übersetzungsschlüssel zurück
	 * @return array Array von Strings
	 * @api
	 */
	public static function getTranslationKeys() {
		return self::getDatabaseConnection()->get( 'SELECT DISTINCT `key`,`comment` FROM `%TABLE` WHERE `key` IS NOT NULL ORDER BY `key`,`comment` ASC' );
	}


	/** Gibt alle Übersetzungsschlüssel zurück, die in der Sprache noch nicht definiert wurden
	 * @param mixed $languageCodeOrInstance
	 * @return array Array von Strings
	 * @api
	 */
	public static function getTranslationKeysNotDefinedInLanguage( $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$sql = '
			SELECT table2.`id`, table2.`key`, table2.`comment`
			FROM (
				SELECT DISTINCT `key`
				FROM `%TABLE`
				WHERE `language` != "'. $Language->shortCode .'"
			) as table1,
			`%TABLE` as table2
			WHERE table2.`key` = table1.`key`
			AND table1.`key` NOT IN (
				SELECT DISTINCT `key`
				FROM `%TABLE`
				WHERE `language` = "'. $Language->shortCode .'"
			)
			GROUP BY table2.`key`, table2.`comment`
		';
		return self::getDatabaseConnection()->get( $sql );
	}


	/** Gibt alle Übersetzungen zurück
	 * @return array Array von DictionaryTranslation Instanzen
	 * @api
	 */
	public static function getTranslations( $limit=null, $start=0 ) {
		return self::getDatabaseConnection()->getAll( null, 'ORDER BY `id` DESC ' .($limit !== null ? 'LIMIT '. intval($start) .','. intval($limit) : '') );
	}


	/** Findet alle Übersetzungen anhand des Schlüssels
	 * @param string $key
	 * @return array DictionaryTranslation-Objekte
	 * @api
	 */
	public static function getTranslationsByKey( $key ) {
		$key = trim( $key );
		return self::getByColumns( array('key' => $key), true );
	}


	/** Gibt alle Übersetzungen einer Sprache zurück
	 * @param mixed $languageCodeOrInstance
	 * @return array DictionaryTranslation-Objekte
	 * @api
	 */
	public static function getTranslationsByLanguage( $languageCodeOrInstance, $limit=null, $start=0 ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		if( $limit === null )
			return self::getByColumns( array('language' => $Language->shortCode), true );
		return self::getDatabaseConnection()->getAll( '`language`="'. $Language->shortCode .'"', 'ORDER BY `id` DESC ' .($limit !== null ? 'LIMIT '. intval($start) .','. intval($limit) : '') );
	}


	/** Findet eine Übersetzung anhand des Schlüssels und der Sprache
	 * @param string $key
	 * @param mixed $languageCodeOrInstance
	 * @param boolean $createIfDoesNotExist
	 * @return DictionaryTranslation
	 * @api
	 */
	public static function getTranslation( $languageCodeOrInstance, $key, $comment="", $createIfDoesNotExist=true ) {
		$key = trim( $key );
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$Translation = self::getByColumns( array('key' => $key, 'comment' => $comment,'language' => $Language->shortCode) );
		if( $Translation === null && $createIfDoesNotExist ) {
			$Translation = self::create();
			if( $Translation ) {
				$Translation->language = $Language->shortCode;
				$Translation->key = $key;
				$Translation->comment = $comment;
				$Translation->adopt();
			}
		}
		return $Translation;
	}


	/** Zählt alle vorhandenen Übersetzungen
	 * @return integer
	 * @api
	 */
	public static function countTranslations() {
		return self::totalCount();
	}


	/** Zählt alle vorhandenen Übersetzungen einer Sprache
	 * @param mixed $languageCodeOrInstance
	 * @return integer
	 * @api
	 */
	public static function countTranslationsByLanguage( $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$count = intval( self::count( '`language` = "'. $Language->shortCode .'"' ) );
		return $count;
	}


	/** Zählt alle vorhandenen Übersetzungsschlüssel
	 * @return integer
	 * @api
	 */
	public static function countTranslationKeys() {
		$count = self::getDatabaseConnection()->getOne( 'SELECT COUNT(DISTINCT `key`) as count FROM `%TABLE`' );
		return intval( $count->get('count') );
	}


/* Private methods */

	protected static function getLanguageInstance( $languageCodeOrInstance ) {
		if( $languageCodeOrInstance instanceof Language )
			return $languageCodeOrInstance;
		elseif( is_string( $languageCodeOrInstance ) )
			return Language::getLanguageByShortCode( $languageCodeOrInstance );
		return null;
	}


	protected function encodeKey( $value ) {
		return trim( $value );
	}


	protected function encodeTranslation( $value ) {
		return trim( $value );
	}


	protected function decodeTranslation( $value ) {
		if( !$value || strlen( $value ) == 0 )
			return $this->key;
		return $value;
	}


/* Public methods */

	public function getTranslatedString() {
		return $this->translation ? $this->translation : $this->key;
	}


	public function __toString() {
		return $this->translation;
	}


}