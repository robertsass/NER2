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
interface LocalizationInterface {

	static function getUseragentLanguages();
	static function getPrimaryUseragentLanguage();
	static function getLocale();
	static function getLanguage();
	static function getRegion();
	static function setLanguage( $language );
	static function extractLanguageCode( $locale );
	static function extractRegionCode( $locale );
	static function restoreLanguageSelection( $considerGetParameter );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Localization implements LocalizationInterface {


	private static $_languages;
	private static $_selectedLanguage;


	/** Gibt die Sprachen des Browsers zurück
	 *
	 * @access public
	 * @return array Geordnetes Array mit Sprachschlüsseln
	 */
	public static function getUseragentLanguages() {
		if( !self::$_languages ) {
			self::$_languages = array();
			foreach( Useragent::detectLanguages() as $language => $score )
				self::$_languages[] = $language;
		}
		return self::$_languages;
	}


	/** Gibt die favorisierte Sprache des Browsers zurück
	 *
	 * @access public
	 * @return string Sprachschlüssel
	 */
	public static function getPrimaryUseragentLanguage() {
		return current( self::getUseragentLanguages() );
	}


	/** Gibt die Standard Sprache zurück
	 *
	 * @access public
	 * @return string Sprachschlüssel
	 */
	public static function getDefaultLanguage() {
		$languages = \Brainstage\Language::getDatabaseConnection()->getAll( null, 'ORDER BY `id` ASC' );
		if( !empty( $languages ) ) {
			$Language = current( $languages );
			if( $Language )
				return $Language->shortCode;
		}
		return null;
	}


	/** Gibt die gewählte Locale oder die des Browsers zurück
	 *
	 * @access public
	 * @return string Locale
	 */
	public static function getLocale( $restoreLanguageSelection=true ) {
		if( $restoreLanguageSelection )
			self::restoreLanguageSelection();
		if( self::$_selectedLanguage )
			return self::$_selectedLanguage;
		return self::getPrimaryUseragentLanguage();
	}


	/** Gibt die gewählte Sprache oder die primäre Browser-Sprache zurück
	 *
	 * @access public
	 * @return string Sprachschlüssel
	 */
	public static function getLanguage( $restoreLanguageSelection=true ) {
		if( $restoreLanguageSelection )
			self::restoreLanguageSelection();
		if( self::$_selectedLanguage )
			return self::extractLanguageCode( self::$_selectedLanguage );
		$languageCode = self::extractLanguageCode( self::getPrimaryUseragentLanguage() );
		if( !$languageCode )
			$languageCode = self::extractLanguageCode( self::getDefaultLanguage() );
		return $languageCode;
	}


	/** Gibt die Region der gewählten Sprache oder der primären Browser-Sprache zurück
	 *
	 * @access public
	 * @return string Regionskürzel
	 */
	public static function getRegion( $restoreLanguageSelection=true ) {
		if( $restoreLanguageSelection )
			self::restoreLanguageSelection();
		if( self::$_selectedLanguage )
			return self::extractRegionCode( self::$_selectedLanguage );
		return self::extractRegionCode( self::getPrimaryUseragentLanguage() );
	}


	/** Wählt eine Sprache
	 *
	 * @access public
	 * @param string $language Sprachschlüssel
	 * @return void
	 */
	public static function setLanguage( $language, $rememberInSession=true ) {
		self::$_selectedLanguage = $language;
		if( $rememberInSession ) {
			$_SESSION['Core.language'] = $language;
		}
	}


	/** Extrahiert das Sprachkürzel aus einer Locale
	 *
	 * @access public
	 * @param string $locale Locale (z.B. en-US, de-CH, de-DE)
	 * @return string Sprachkürzel
	 */
	public static function extractLanguageCode( $locale ) {
		$locales = explode( '-', str_replace( '_', '-', strtolower( $locale ) ), 2 );
		return current( $locales );
	}


	/** Extrahiert das Regionskürzel aus einer Locale
	 *
	 * @access public
	 * @param string $locale Locale (z.B. en-US, de-CH, de-DE)
	 * @return string Regionskürzel
	 */
	public static function extractRegionCode( $locale ) {
		$locales = explode( '-', str_replace( '_', '-', strtolower( $locale ) ), 2 );
		return array_pop( $locales );
	}


	/** Stellt die Spracheinstellung aus der Session wieder her
	 *
	 * @access public
	 * @return void
	 */
	public static function restoreLanguageSelection( $considerGetParameter=true ) {
		if( isset( $_SESSION['Core.language'] ) )
			self::setLanguage( $_SESSION['Core.language'] );
		if( isset( $_SESSION['locale'] ) )
			self::setLanguage( self::extractLanguageCode( $_SESSION['locale'] ) );
		elseif( isset( $_SESSION['language'] ) )
			self::setLanguage( self::extractLanguageCode( $_SESSION['language'] ) );
		elseif( $considerGetParameter ) {
			if( isset( $_GET['locale'] ) )
				self::setLanguage( self::extractLanguageCode( $_GET['locale'] ) );
			elseif( isset( $_GET['language'] ) )
				self::setLanguage( self::extractLanguageCode( $_GET['language'] ) );
		}
		if( self::$_selectedLanguage )
			return true;
		return false;
	}


}