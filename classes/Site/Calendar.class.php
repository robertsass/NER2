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
interface CalendarInterface {

	function generateDownload();
	function generateString();

	function addEvent( CalendarEvent $Event );
	function setTitle( $title );
	function setAuthor( $author );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Calendar extends \rsCore\CoreClass implements CalendarInterface {

	protected $events;
	protected $title;
	protected $author;


	public function __construct( $parameters ) {
		$parameters = array_merge( array(
		  'events' => array(),
		  'title' => 'Calendar',
		  'author' => 'Calender Generator'
		), $parameters );
		$this->events = $parameters['events'];
		$this->title  = $parameters['title'];
		$this->author = $parameters['author'];
	}


	/** Erzwingt den Download der Ausgabe
	 * @return string
	 * @api
	 */
	public function generateDownload() {
		$filename = $this->title ? $this->title : 'calendar';
		$string = $this->generateString();
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); //date in the past
		header( 'Last-Modified: '. gmdate( 'D, d M Y H:i:s' ) .' GMT' ); //tell it we just updated
		header( 'Cache-Control: no-store, no-cache, must-revalidate' ); //force revaidation
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header( 'Content-type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="'. $filename .'.ics"' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: '. strlen( $string ) );
		die( $string );
	}


	/** Generiert die Ausgabe im iCal-Format
	 * @return string
	 * @api
	 */
	public function generateString() {
		$string = "BEGIN:VCALENDAR\r\n"
				. "VERSION:2.0\r\n"
				. "PRODID:-//". $this->author ."//NONSGML//EN\r\n"
				. "X-WR-CALNAME:". $this->title ."\r\n"
				. "CALSCALE:GREGORIAN\r\n";

		foreach( $this->events as $Event ) {
			$string .= $Event->generateString();
		}
		$string .= "END:VCALENDAR";
		return $string;
	}


	/** Fügt dem Kalender ein Event hinzu
	 * @param CalendarEvent $Event
	 * @return CalendarBuilder Selbstreferenz
	 * @api
	 */
	public function addEvent( CalendarEvent $Event ) {
		$this->events[] = $Event;
		return $this;
	}


	/** Setzt den Titel des Kalendars
	 * @param string $title
	 * @return CalendarBuilder Selbstreferenz
	 * @api
	 */
	public function setTitle( $title ) {
		$this->title = $title;
		return $this;
	}


	/** Setzt den Autor des Kalendars
	 * @param string $author
	 * @return CalendarBuilder Selbstreferenz
	 * @api
	 */
	public function setAuthor( $author ) {
		$this->author = $author;
		return $this;
	}


}