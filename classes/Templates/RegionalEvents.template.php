<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Templates;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface RegionalEventsInterface {

	function serveCalendarFile();
	function getWebcalURL();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class RegionalEvents extends Base implements RegionalEventsInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendContentArea' );
		$this->hook( 'extendOnepage' );
		$this->hook( 'extendSidebar' );

		if( isset( $_GET['ical'] ) )
			$this->serveCalendarFile();
	}


	/** Hook zum Erweitern des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContentArea( \rsCore\Container $Container ) {
		$Container->subordinate( 'a.btn.btn-default', array('href' => $this->getWebcalURL()) )->subordinate( 'span.nighticon-calendar' )->append( t("Subscribe to calendar") );
		return $this->buildEventsList( $Container );
	}


	/** Hook zum Erweitern der Onepage
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendOnepage( \rsCore\Container $Container ) {
	#	$Container->subordinate( 'a.btn.btn-default', array('href' => $this->getWebcalURL()) )->subordinate( 'span.nighticon-calendar' )->append( t("Subscribe to calendar") );
	#	return $this->buildEventsList( $Container );
	}


	/** Hook zum Erweitern der Sidebar
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendSidebar( \rsCore\Container $Container ) {
		return $this->buildEventsWidget( $Container );
	}


	/** Baut die Termin-Liste
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildEventsList( \rsCore\Container $Container ) {
		$List = $Container->subordinate( 'ul.events-list' );
		$dateFormat = t("Y-m-d H:i", 'Date and Time: full year, without seconds');
		$events = $this->getCity()->getEvents();
		if( is_array( $events ) ) {
			$currentYear = null;
			foreach( $events as $Event ) {
				if( $currentYear != $Event->start->format( 'Y' ) ) {
					$currentYear = $Event->start->format( 'Y' );
					$List->subordinate( 'li.year-break', $currentYear );
				}

				$ListEntry = $List->subordinate( 'li' );
				$ListEntry->subordinate( 'a', array('name' => $Event->getPrimaryKeyValue()) );

				$DateFlag = $ListEntry->subordinate( 'span.date' );
				$DateFlag->subordinate( 'span.day', $Event->start->format( 'd' ) );
				$DateFlag->subordinate( 'span.month', t( $Event->start->format( 'F' ) ) );

				$ListEntry->subordinate( 'span.time', $Event->start->format( t("H:i", 'Time: hours and minutes') ) .' '. t("o'clock") );
				$ListEntry->subordinate( 'span.title', $Event->getTitle() );
				$ListEntry->subordinate( 'span.location', $Event->getLocation()->name );
				$ListEntry->subordinate( 'span.description', $Event->getDescriptionTable() );
			}
		}
	}


	/** Baut das Sidebar-Termin-Widget
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildEventsWidget( \rsCore\Container $Container ) {
		$Head = $Container->subordinate( 'div.head' );
		$Head->subordinate( 'h4', t("Events") );
		$Head->subordinate( 'a.btn.btn-default.btn-xs.nighticon-rss', array(
			'href' => $this->getWebcalURL(),
			'title' => t("Subscribe to calendar"),
			'data-toggle' => 'tooltip',
			'data-placement' => 'top'
		) );
		$List = $Container->subordinate( 'ul.events-widget' );
		$dateFormat = t("m-d", 'Date without year');
		$events = $this->getCity()->getEvents();

		if( is_array( $events ) ) {
			foreach( $events as $Event ) {
				$link = $this->getDocument()->getComposedUrl() .'#'. $Event->getPrimaryKeyValue();
				$Location = $Event->getLocation();

				$ListEntry = $List->subordinate( 'li' );

				$ListEntry->subordinate( 'span.date > a', array(
					'href' => $link,
					'title' => $Event->start->format( t("H:i", 'Time: hours and minutes') ) .' '. t("o'clock"),
					'data-toggle' => 'tooltip',
					'data-placement' => 'left'
				), $Event->start->format( t("m-d", 'Date without year') ) );

				$ListEntry->subordinate( 'span.title', $Event->getShortTitle() );
				$ListEntry->subordinate( 'span.location', t("in", 'Location') .' ' )
					->subordinate( 'a', array(
						'href' => $this->getChildUrlByTemplate( 'RegionalLocation' ) .'#'. $Location->getPrimaryKeyValue()
					), $Location->name );
			}
		}
	}


	/** Baut den Termin-Kalender
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildEventsCalendar( \rsCore\Container $Container ) {
#		return $this->buildEventsList( $Container );
	}


	/** Gibt die Veranstaltungen als iCal-Datei aus
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function serveCalendarFile() {
		$events = $this->getCity()->getEvents();
		$title = t("Nightfever") .' '. $this->getCity()->name;
		\Nightfever\Nightfever::serveCalendarFile( $events, $title );
	}


	/** Gibt die Webcal-URL zur aktuellen Seite zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getWebcalURL() {
		$RequestPath = requestPath()->getClone();
		$RequestPath->scheme = 'webcal';
		$parameters = array('ical' => '1', 'language' => \rsCore\Localization::getLanguage());
		$RequestPath->parameters = array_merge( $RequestPath->parameters, $parameters );
		return $RequestPath->buildURL();
	}


}