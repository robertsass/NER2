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
interface LanguageInterface {

	static function addLanguage( $name, $shortCode, $locale );

	static function getLanguages();
	static function getLanguageByName( $name );
	static function getLanguageByLocale( $locale );
	static function getLanguageByShortCode( $shortCode );
	static function getLanguageInstance( $languageCodeOrInstance );

	function getRegionCode();

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Language extends \rsCore\DatabaseDatasetAbstract implements LanguageInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-languages';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Fügt eine Sprache hinzu
	 * @param string $name
	 * @param string $shortCode Zweistelliger Sprachkürzel, z.B.: de
	 * @param string $locale Kombination aus Sprach- und Regionskürzel, z.B.: de_CH
	 * @return Language
	 * @api
	 */
	public static function addLanguage( $name, $shortCode, $locale=null ) {
		$name = trim( $name );
		$shortCode = strtolower( trim( $shortCode ) );

		$Language = self::getByColumns( array('name' => $name) );
		if( $Language !== null )
			throw new \rsCore\Exception( 'Language "'. $name .'" already exists.' );

		$Language = self::getByColumns( array('shortCode' => $shortCode) );
		if( $Language !== null )
			throw new \rsCore\Exception( 'Language with short code "'. $shortCode .'" already exists.' );

		$Language = self::create();
		if( $Language ) {
			$Language->name = $name;
			$Language->shortCode = $shortCode;
			$Language->locale = $locale;
			$Language->adopt();
		}
		return $Language;
	}


	/** Gibt alle Sprachen zurück
	 * @return array Array von Language Instanzen
	 * @api
	 */
	public static function getLanguages() {
		return self::getDatabaseConnection()->getAll( null, 'ORDER BY `name` ASC' );
	}


	/** Findet eine Sprache anhand des vollen Namens
	 * @param string $name
	 * @return Language
	 * @api
	 */
	public static function getLanguageByName( $name ) {
		$name = trim( $name );
		return self::getByColumns( array('name' => $name) );
	}


	/** Findet eine Sprache anhand der Locale
	 * @param string $locale
	 * @return Language
	 * @api
	 */
	public static function getLanguageByLocale( $locale ) {
		$locale = trim( $locale );
		return self::getByColumns( array('locale' => $locale) );
	}


	/** Findet eine Sprache anhand des Kürzels
	 * @param string $shortCode
	 * @return Language
	 * @api
	 */
	public static function getLanguageByShortCode( $shortCode ) {
		$shortCode = strtolower( trim( $shortCode ) );
		return self::getByColumns( array('shortCode' => $shortCode) );
	}


	/** Gibt eine Sprachinstanz zurück oder findet eine anhand der ID
	 * @param mixed $instanceOrId
	 * @return Language
	 * @api
	 */
	public static function getLanguageInstance( $languageCodeOrInstance ) {
		if( $languageCodeOrInstance instanceof Language )
			return $languageCodeOrInstance;
		elseif( is_string( $languageCodeOrInstance ) )
			return self::getLanguageByShortCode( $languageCodeOrInstance );
		return null;
	}


/* Public methods */

	/** Gibt das Regionskürzel zurück
	 * @return string
	 * @api
	 */
	public function getRegionCode() {
		return \rsCore\Localization::extractRegionCode( $this->locale );
	}


	public function __toString() {
		return $this->name;
	}


/* Private methods */

	protected function encodeShortCode( $value ) {
		return strtolower( trim( $value ) );
	}


}