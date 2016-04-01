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
interface CalendarEventInterface {

	function generateString();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CalendarEvent extends \rsCore\CoreClass implements CalendarEventInterface {

	private $uid;
	private $start;
	private $end;
	private $summary;
	private $description;
	private $location;


	public function __construct( $parameters ) {
		$parameters = array_merge( array(
		  'summary' => 'Untitled Event',
		  'description' => '',
		  'location' => ''
		), $parameters );
		if( isset( $parameters['uid'] ) )
			$this->uid = $parameters['uid'];
		else
			$this->uid = uniqid( rand( 0, getmypid() ) );
		$this->start = $parameters['start'];
		$this->end = $parameters['end'];
		$this->summary = $parameters['summary'];
		$this->description = $parameters['description'];
		$this->location = $parameters['location'];
	  return $this;
	}


	/** Formatiert ein gegebenes DateTime-Objekt
	 * @return string
	 */
	private function formatDate( $date ) {
		return $date->format( "Ymd\THis\Z" );
	}


	/* Escape commas, semi-colons, backslashes.
	 * http://stackoverflow.com/questions/1590368/should-a-colon-character-be-escaped-in-text-values-in-icalendar-rfc2445
	 * @param string $string
	 * @return string
	 */
	private function formatValue( $string ) {
		return addcslashes( $string, ",\\;" );
	}


	/** Generiert die Ausgabe im iCal-Format
	 * @return string
	 * @api
	 */
	public function generateString() {
		$now = new \DateTime();
		$string = "BEGIN:VEVENT\r\n"
				. "UID:". $this->uid ."\r\n"
				. "DTSTART:". $this->formatDate( $this->start ) ."\r\n"
				. "DTEND:". $this->formatDate( $this->end ) ."\r\n"
				. "DTSTAMP:". $this->formatDate( $this->start ) ."\r\n"
				. "CREATED:". $this->formatDate( $now) ."\r\n"
				. "DESCRIPTION:". $this->formatValue( $this->description ) ."\r\n"
				. "LAST-MODIFIED:". $this->formatDate( $this->start ) ."\r\n"
				. "LOCATION:". $this->location ."\r\n"
				. "SUMMARY:". $this->formatValue( $this->summary ) ."\r\n"
				. "SEQUENCE:0\r\n"
				. "STATUS:CONFIRMED\r\n"
				. "TRANSP:OPAQUE\r\n"
				. "END:VEVENT\r\n";
		return $string;
	}


}