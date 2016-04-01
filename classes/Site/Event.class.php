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
interface EventInterface {

	public static function addEvent( Site $Site );

	public static function getEventById( $eventId );
	public static function getEventsBySite( Site $Site );
	public static function getEventsByCity( City $City );
	public static function getEventsByCountry( Country $Country );
	public static function getEventsByLocation( Location $Location );
	public static function getEventsByTimeframe( $startTimestamp, $endTimestamp, Site $Site );
	public static function getEventsByTitle( $title );

	public function getCity();
	public function getSite();
	public function getLocation();
	public function getMeta( $languageCodeOrInstance );
	public function getTitle( $languageCodeOrInstance );
	public function getShortTitle( $languageCodeOrInstance );
	public function getDescription( $languageCodeOrInstance );
	public function getDescriptionTable( $languageCodeOrInstance );

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Event extends \rsCore\DatabaseDatasetAbstract implements EventInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'events';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return Event
	 * @api
	 */
	public static function addEvent( Site $Site ) {
		if( !$Site )
			return null;
		$Event = self::create();
		$Event->siteId = $Site->getPrimaryKeyValue();
		$Event->adopt();
		return $Event;
	}


	/** Gibt ein Event anhand seiner ID zurück
	 * @param integer $eventId
	 * @return Event
	 * @api
	 */
	public static function getEventById( $eventId ) {
		return self::getByPrimaryKey( $eventId );
	}


	/** Gibt alle Events einer Site (Country/City, i.d.R. City) zurück
	 * @param Site $Site
	 * @return array Array von Event-Instanzen
	 * @api
	 */
	public static function getEventsBySite( Site $Site, $onlyFutureEvents=false, $limit=null, $start=0 ) {
		if( $Site instanceof Country )
			return self::getEventsByCountry( $Site, $onlyFutureEvents, $limit, $start );
		elseif( $Site instanceof City )
			return self::getEventsByCity( $Site, $onlyFutureEvents, $limit, $start );

		$conditions = array();
		$conditions[] = '`siteId` = "'. $Site->getPrimaryKeyValue() .'"';
		if( $onlyFutureEvents )
			$conditions[] = '`start` > "'. \rsCore\DatabaseConnector::encodeDatetime( time() ) .'"';
		$condition = '('. implode( ' AND ', $conditions ) .')';
		$condition .= ' ORDER BY `start` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT '. intval($start) .','. intval($limit);
		return self::getAll( $condition );
	}


	/** Gibt alle Events einer Stadt zurück
	 * @param City $City
	 * @return array Array von Event-Instanzen
	 * @api
	 */
	public static function getEventsByCity( City $City, $onlyFutureEvents=false, $limit=null, $start=0 ) {
		$conditions = array();
		$conditions[] = '`siteId` = "'. $City->getPrimaryKeyValue() .'"';
		if( $onlyFutureEvents )
			$conditions[] = '`start` > "'. \rsCore\DatabaseConnector::encodeDatetime( time() ) .'"';
		$condition = '('. implode( ' AND ', $conditions ) .')';
		$condition .= ' ORDER BY `start` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT '. intval($start) .','. intval($limit);
		return self::getAll( $condition );
	}


	/** Gibt alle Events eines Landes zurück
	 * @param Country $Country
	 * @return array Array von Event-Instanzen
	 * @api
	 */
	public static function getEventsByCountry( Country $Country, $onlyFutureEvents=false, $limit=null, $start=0 ) {
		$siteIds = array( $Country->getPrimaryKeyValue() => $Country->getPrimaryKeyValue() );
		foreach( $Country->getCities() as $City ) {
			$siteIds[ $City->getPrimaryKeyValue() ] = $City->getPrimaryKeyValue();
		}
		$conditions = array();
		foreach( $siteIds as $siteId )
			$conditions[] = '`siteId` = "'. $siteId .'"';
		$condition = '('. implode( ' OR ', $conditions ) .')';
		if( $onlyFutureEvents )
			$condition .= ' AND `start` > "'. \rsCore\DatabaseConnector::encodeDatetime( time() ) .'"';
		$condition .= ' ORDER BY `start` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT '. intval($start) .','. intval($limit);
		return self::getAll( $condition );
	}


	/** Gibt alle Events einer Location zurück
	 * @param Location $Location
	 * @return array Array von Event-Instanzen
	 * @api
	 */
	public static function getEventsByLocation( Location $Location ) {
		return self::getByColumn( 'locationId', $Location->getPrimaryKeyValue(), true, array('start' => 'ASC') );
	}


	/** Gibt alle Events zurück
	 * @param mixed $xyzxyz
	 * @return Event
	 * @api
	 */
	public static function getEventsByTimeframe( $startTimestamp, $endTimestamp=null, Site $Site=null, $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`start` >= "'. $DatabaseConnector::encodeDatetime( $startTimestamp ) .'"';
		if( $endTimestamp !== null )
			$condition .= ' AND `end` <= "'. $DatabaseConnector::encodeDatetime( $endTimestamp ) .'"';
		if( $Site !== null )
			$condition .= ' AND `siteId` = "'. $Site->getPrimaryKeyValue() .'"';
		$condition .= ' ORDER BY `start` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Gibt alle Events mit einem bestimmten Titel zurück
	 * @param string $title
	 * @return array Array von Event-Instanzen
	 * @api
	 */
	public static function getEventsByTitle( $title ) {
		return self::getByColumn( 'title', $title, true, array('start' => 'ASC') );
	}


/* Public methods */

	/** Gibt die Stadt zurück, in dessen Namen das Event stattfindet
	 * @return City
	 * @api
	 */
	public function getCity() {
		return Sites::getCityById( $this->siteId );
	}


	/** Gibt die Site zurück, in dessen Namen das Event stattfindet
	 * @return Site
	 * @api
	 */
	public function getSite() {
		return Sites::getSiteById( $this->siteId );
	}


	/** Gibt die Location zurück, in dem dieses Event stattfindet
	 * @return Location
	 * @api
	 */
	public function getLocation() {
		return Location::getLocationById( $this->locationId );
	}


	/** Gibt die lokalisierten Metadaten dieses Events zurück
	 * @param string $languageCodeOrInstance
	 * @return EventMeta
	 * @api
	 */
	public function getMeta( $languageCodeOrInstance=null ) {
		if( $languageCodeOrInstance === null )
			$languageCodeOrInstance = \Brainstage\Language::getLanguageByShortCode( \rsCore\Localization::getLanguage() );
		$Meta = EventMeta::getMetaByEvent( $this, $languageCodeOrInstance );
		if( $Meta === null )
			$Meta = EventMeta::addEventMeta( $this, $languageCodeOrInstance );
		return $Meta;
	}


	/** Gibt den lokalisierten Titel des Events zurück
	 * @param string $locationCodeOrInstance
	 * @return string
	 * @api
	 */
	public function getTitle( $languageCodeOrInstance=null ) {
		$Meta = $this->getMeta( $locationCodeOrInstance );
		if( $Meta )
			return $Meta->title;
		return null;
	}


	/** Gibt den lokalisierten Titel des Events -gekürzt um den Städtenamen- zurück
	 * @param string $locationCodeOrInstance
	 * @return string
	 * @api
	 */
	public function getShortTitle( $languageCodeOrInstance=null ) {
		$Meta = $this->getMeta( $locationCodeOrInstance );
		if( $Meta ) {
			$title = $Meta->title;
			$Site = $this->getSite();
			if( $Site && strpos( $title, $Site->name ) !== false ) {
				$nameLength = strlen( $Site->name );
				if( substr( $title, -$nameLength, $nameLength ) == $Site->name )
					$title = trim( substr( $title, 0, -$nameLength ) );
			}
			return $title;
		}
		return null;
	}


	/** Gibt die lokalisierte Beschreibung des Events zurück
	 * @param string $locationCodeOrInstance
	 * @return string
	 * @api
	 */
	public function getDescription( $languageCodeOrInstance=null ) {
		$Meta = $this->getMeta( $locationCodeOrInstance );
		if( $Meta )
			return $Meta->description;
		return null;
	}


	/** Gibt die lokalisierte Beschreibung des Events als formatierte Tabelle zurück
	 * @param string $locationCodeOrInstance
	 * @return \rsCore\Container
	 * @api
	 */
	public function getDescriptionTable( $languageCodeOrInstance=null ) {
		$Meta = $this->getMeta( $locationCodeOrInstance );
		if( $Meta )
			return $Meta->getDescriptionTable();
		return null;
	}


/* Private methods */

	protected function encodeStart( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


	protected function decodeStart( $value ) {
		return \rsCore\DatabaseConnector::decodeDatetime( $value );
	}


	protected function encodeEnd( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


	protected function decodeEnd( $value ) {
		return \rsCore\DatabaseConnector::decodeDatetime( $value );
	}


}