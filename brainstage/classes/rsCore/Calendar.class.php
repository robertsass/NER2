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
interface CalendarInterface {

	static function parse( $format, $value );
	static function parseDateTime( \DateTime $Moment );

	static function localizeFormat( $format );

	function setDateTime();
	function getDateTime();

	function setTimestamp();
	function getTimestamp();

	function format( $format, $localizeDateFormat=true );

	function getMonth( $format );
	function getDay();
	function getWeekday( $format );
	function getYear();

	function getDayBeginningTimestamp();
	function getDayEndingTimestamp();

	function getMonthsDays();
	function getMonthsFirstDay();
	function getMonthsLastDay();

	function getNextDay();
	function getNextMonth();
	function getPreviousDay();
	function getPreviousMonth();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Calendar extends CoreClass {


	protected static $localizableDateFormats = array(
			'Y-m-d H:i:s' => "Date and Time: full year, hours, minutes and seconds",
			'Y-m-d H:i' => "Date and Time: full year, without seconds",
			'Y-m-d' => "Date with full year",
			'm-d' => "Date without year",
			'H:i' => "Time: hours and minutes"
		);

	protected $DateTimeObject;


	/** Parst das Datum anhand des übergebenen Formates.
	 *
	 * @param string $format
	 * @param string $value
	 * @return Calendar
	 */
	public static function parse( $format, $value ) {
		$DateTime = \DateTime::createFromFormat( $format, $value );
		if( !$DateTime )
			$DateTime = \DateTime::createFromFormat( static::localizeFormat( $format ), $value );
		if( $DateTime )
			return new static( $DateTime );
		return null;
	}


	/** Schluckt ein natives \DateTime Objekt
	 *
	 * @param \DateTime $Moment
	 * @return Calendar
	 */
	public static function parseDateTime( \DateTime $Moment ) {
		return new static( $Moment );
	}


	/** Parst das Datum anhand des übergebenen Formates.
	 *
	 * @param string $format
	 * @param string $value
	 * @return Calendar
	 */
	public static function localizeFormat( $format ) {
		if( array_key_exists( $format, static::$localizableDateFormats ) ) {
			return t( $format, static::$localizableDateFormats[ $format ] );
		}
		return $format;
	}


	/** Setzt den Ausgangszeitpunkt und übersetzt die Monats- und Wochentage.
	 *
	 * @param $date - Optionale Angabe eines Ausgangszeitpunktes als UNIX-Zeitstempel oder DateTime-Objekt, sonst wird der Wert von time() verwendet.
	 */
	public function __construct( $date=null ) {
		if( $date === null )
			$this->setTimestamp( time() );
		elseif( is_object( $date ) && $date instanceof \DateTime )
			$this->setDateTime( $date );
		elseif( is_int( $date ) )
			$this->setTimestamp( $date );
		else
			throw new \Exception( "Could not parse the given date." );
	}


	/** Setzt das DateTime-Objekt des durch dieses Objekt repräsentierten Datums.
	 *
	 * @param \DateTime $DateTimeObject
	 */
	public function setDateTime( \DateTime $DateTimeObject ) {
		$this->DateTimeObject = $DateTimeObject;
	}


	/** Gibt das DateTime-Objekt des durch dieses Objekt repräsentierten Datums zurück.
	 *
	 * @return \DateTime
	 */
	public function getDateTime() {
		return $this->DateTimeObject;
	}


	/** Setzt den UNIX-Zeitstempel des durch dieses Objekt repräsentierten Datums.
	 *
	 * @param int $timestamp
	 */
	public function setTimestamp( $timestamp ) {
		$this->DateTimeObject = \DateTime::createFromFormat( 'U', $timestamp );
	}


	/** Gibt den UNIX-Zeitstempel des durch dieses Objekt repräsentierten Datums zurück.
	 */
	public function getTimestamp() {
		return $this->getDateTime()->getTimestamp();
	}


	/** Formatiert das repräsentierte Datum gemäß dem übergebenen date()-Format.
	 *
	 * @param string $format
	 * @param boolean $localizeDateFormat
	 * @return mixed
	 */
	public function format( $format, $localizeDateFormat=true ) {
		if( $localizeDateFormat )
			$format = static::localizeFormat( $format );
		return $this->getDateTime()->format( $format );
	}


	/** Gibt den Monatsnamen zurück.
	 *
	 * @param int $format 1 = Abkürzung (Jan), 2 = Monatsname (January), 0 = Zahl (1)
	 * @return string
	 */
	public function getMonth( $format=0 ) {
		if( $format == 1 )
			return $this->format( 'M' );
		elseif( $format >= 2 )
			return $this->format( 'F' );
		return $this->format( 'n' );
	}


	/** Gibt den Tag des Monats zurück.
	 *
	 * @return string
	 */
	public function getDay() {
		return $this->format( 'd' );
	}


	/** Gibt den Wochentag zurück.
	 *
	 * @param int $format 1 = Abkürzung (Mon), 2 = Monatsname (Monday), 0 = Zahl nach ISO-8601 (1)
	 * @return string
	 */
	public function getWeekday( $format=0 ) {
		if( $format == 1 )
			return $this->format( 'D' );
		elseif( $format >= 2 )
			return $this->format( 'l' );
		return $this->format( 'N' );
	}


	/** Gibt die Jahreszahl zurück.
	 *
	 * @return int
	 */
	public function getYear() {
		return $this->format( 'Y' );
	}


	/** Gibt den UNIX-Zeitstempel von 0:00 Uhr des repräsentierten Datums zurück.
	 *
	 * @return int
	 */
	public function getDayBeginningTimestamp() {
		return \DateTime::createFromFormat( 'Y-m-d H:i:s', $this->format('Y-m-d') .' 00:00:00' )->getTimestamp();
	}


	/** Gibt den UNIX-Zeitstempel von 23:59:59 Uhr des repräsentierten Datums zurück.
	 *
	 * @return int
	 */
	public function getDayEndingTimestamp() {
		return $this->getDayBeginningTimestamp() + 86399;
	}


	/** Gibt die Anzahl an Tagen des jeweiligen Monats des repräsentierten Datums zurück.
	 *
	 * @return int
	 */
	public function getMonthsDays() {
		return $this->format( 't' );
	}


	/** Gibt ein neues Calendar-Objekt des ersten Monatstages im repräsentierten Monat zurück.
	 *
	 * @return Calendar
	 */
	public function getMonthsFirstDay() {
		return new Calendar( \DateTime::createFromFormat( 'Y-m-d', $this->format('Y-m') .'-01' ) );
	}


	/** Gibt ein neues Calendar-Objekt des letzten Monatstages im repräsentierten Monat zurück.
	 *
	 * @return Calendar
	 */
	public function getMonthsLastDay() {
		return new Calendar( \DateTime::createFromFormat( 'Y-m-d', $this->format('Y-m') .'-'. $this->getMonthsDays() ) );
	}


	/** Gibt ein neues Calendar-Objekt des nächsten Tages relativ zum repräsentierten Datum zurück.
	 *
	 * @return Calendar
	 */
	public function getNextDay() {
		$nextDaysTimestamp = $this->getTimestamp() + 86400;
		return new Calendar( $nextDaysTimestamp );
	}


	/** Gibt ein neues Calendar-Objekt des ersten Tages des nächsten Monats relativ zum repräsentierten Datum zurück.
	 *
	 * @return Calendar
	 */
	public function getNextMonth() {
		return $this->getMonthsLastDay()->getNextDay();
	}


	/** Gibt ein neues Calendar-Objekt des vorherigen Tages relativ zum repräsentierten Datum zurück.
	 *
	 * @return Calendar
	 */
	public function getPreviousDay() {
		$prevDaysTimestamp = $this->getTimestamp() - 86400;
		return new Calendar( $prevDaysTimestamp );
	}


	/** Gibt ein neues Calendar-Objekt des letzten Tages des vorherigen Monats relativ zum repräsentierten Datum zurück.
	 *
	 * @return Calendar
	 */
	public function getPreviousMonth() {
		return $this->getMonthsFirstDay()->getPreviousDay();
	}


	/** Gibt true zurück, falls das repräsentierte Datum ein Montag ist.
	 *
	 * @return boolean
	 */
	public function isWeekbegin() {
		return $this->getWeekday() == 1;
	}


}