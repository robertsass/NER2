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
interface EventMetaInterface {

	public static function addEventMeta( Event $Event, $languageCodeOrInstance );

	public static function getMetaByEvent( Event $Event, $languageCodeOrInstance );

	public function getDescriptionTable();
	public function getEvent();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class EventMeta extends \rsCore\DatabaseDatasetAbstract implements EventMetaInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'event-meta';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return EventMeta
	 * @api
	 */
	public static function addEventMeta( Event $Event, $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$EventMeta = self::create();
		$EventMeta->eventId = $Event->getPrimaryKeyValue();
		$EventMeta->language = $Language->shortCode;
		$EventMeta->adopt();
		return $EventMeta;
	}


	/** Gibt alle EventMetas mit einem bestimmten Titel zurück
	 * @param string $title
	 * @return array Array von EventMeta-Instanzen
	 * @api
	 */
	public static function getMetaByEvent( Event $Event, $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		return self::getByColumns( array('eventId' => $Event->getPrimaryKeyValue(), 'language' => $Language->shortCode) );
	}


/* Public methods */

	/** Gibt die als Tabelle formatierte Beschreibung der Veranstaltung zurück
	 * @return \rsCore\Container
	 * @api
	 */
	public function getDescriptionTable() {
		return self::buildDescriptionTable( $this->description );
	}


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


	protected static function buildDescriptionTable( $text ) {
		$Table = new \rsCore\Container( 'table.table.table-condensed.table-hover' );
		$Head = $Table->subordinate( 'thead > tr' );
		$Head->subordinate( 'th', t("Time") );
		$Head->subordinate( 'th', t("Program item") );
		$Body = $Table->subordinate( 'tbody' );

		$lines = explode( "\n", $text );
		$linesCount = 0;
		foreach( $lines as $line ) {
			$Row = $Body->subordinate( 'tr' );
			$data = explode( '/', $line, 2 );
			$time = trim( $data[0] );
			$programItem = trim( $data[1] );
			if( $time || $programItem ) {
				$Row->subordinate( 'td', $time );
				$Row->subordinate( 'td', $programItem );
				$linesCount++;
			}
		}
		if( $linesCount > 0 )
			return $Table;
		return null;
	}


	protected function encodeTitle( $value ) {
		return strip_tags( $value );
	}


	protected function decodeTitle( $value ) {
		return strip_tags( $value );
	}


	protected function encodeDescription( $value ) {
		return strip_tags( $value );
	}


	protected function decodeDescription( $value ) {
		return strip_tags( $value );
	}


}