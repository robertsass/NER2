<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Site;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface QuoteInterface {

	public static function addQuote( $languageCodeOrInstance );

	public static function getQuoteById( $quoteId );
	public static function getQuotes( $limit, $start, $sortingDescending );
	public static function getQuotesByLanguage( $languageCodeOrInstance );

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Quote extends \rsCore\DatabaseDatasetAbstract implements QuoteInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'quotes';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return Quote
	 * @api
	 */
	public static function addQuote( $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$Quote = self::create();
		$Quote->language = $Language->shortCode;
		$Quote->date = time();
		$Quote->adopt();
		return $Quote;
	}


	/** Gibt eine Quote-Instanz anhand seiner ID zurück
	 * @param integer $quoteId
	 * @return Quite
	 * @api
	 */
	public static function getQuoteById( $quoteId ) {
		return self::getByPrimaryKey( $quoteId );
	}


	/** Gibt alle Quotes zurück
	 * @param integer $limit
	 * @param integer $start
	 * @return array Array von Quote-Instanzen
	 * @api
	 */
	public static function getQuotes( $limit=null, $start=0, $sortingDescending=true ) {
		$condition = array('1=1');
		$condition = implode( ' AND ', $condition );
		$condition .= ' ORDER BY `id` '. ($sortingDescending ? 'DESC' : 'ASC');
		if( $limit !== null )
			$condition .= ' LIMIT '. intval($start) .','. intval($limit);
		return self::getAll( $condition );
	}


	/** Gibt alle Quotes mit einem bestimmten Titel zurück
	 * @param string $title
	 * @return array Array von Quote-Instanzen
	 * @api
	 */
	public static function getQuotesByLanguage( $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		return self::getByColumns( array('language' => $Language->shortCode), true );
	}


/* Public methods */

	/** Gibt die zu diesem Datensatz gehörende Veranstaltung zurück
	 * @return Event
	 * @api
	 */
	public function getEvent() {
		return Event::getEventById( $this->eventId );
	}


/* Private methods */

	protected static function getLanguageInstance( $languageCodeOrInstance ) {
		if( $languageCodeOrInstance instanceof \Brainstage\Language )
			return $languageCodeOrInstance;
		elseif( is_string( $languageCodeOrInstance ) )
			return \Brainstage\Language::getLanguageByShortCode( $languageCodeOrInstance );
		return null;
	}

	protected function encodeDate( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


	protected function decodeDate( $value ) {
		return \rsCore\DatabaseConnector::decodeDatetime( $value );
	}


}