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
interface BookingInterface {

	public static function addBooking( Client $Client, \DateTime $Begin, \DateTime $End );

	public static function getBookingById( $bookingId );
	public static function getBookingsByTimeframe( \DateTime $Begin, \DateTime $End, $status=null, $limit=null );
	public static function getBookingsAtDay( \DateTime $Day );
	public static function getFutureBookings( $limit=null );
	public static function getPastBookings( $limit=null );
	public static function getApprovedBookings( $pastBookings=false, $limit=null );
	public static function getRequestedBookings( $pastBookings=false, $limit=null );

	public static function countBookingsByTimeframe( \DateTime $Begin, \DateTime $End, $status=null );
	public static function countApprovedFutureBookings();

	public function getClient();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Booking extends \rsCore\DatabaseDatasetAbstract implements BookingInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'bookings';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return Booking
	 * @api
	 */
	public static function addBooking( Client $Client, \DateTime $Begin, \DateTime $End ) {
		if( $Client ) {
			$Now = new \rsCore\Calendar();
			$Booking = self::create();
			$Booking->clientId = $Client->getPrimaryKeyValue();
			$Booking->begin = $Begin;
			$Booking->end = $End;
			$Booking->incomeDate = $Now->getDateTime();
			$Booking->adopt();
			return $Booking;
		}
	}


	/** Gibt ein Booking anhand seiner ID zurück
	 * @param integer $bookingId
	 * @return Booking
	 * @api
	 */
	public static function getBookingById( $bookingId ) {
		return self::getByPrimaryKey( $bookingId );
	}


	/** Gibt alle Bookings zurück
	 * @param \DateTime $Begin
	 * @param \DateTime $End
	 * @param string $status
	 * @param int $limit
	 * @return Booking
	 * @api
	 */
	public static function getBookingsByTimeframe( \DateTime $Begin, \DateTime $End, $status=null, $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`begin` >= "'. $DatabaseConnector::encodeDatetime( $Begin ) .'"';
		if( $End !== null )
			$condition .= ' AND `end` <= "'. $DatabaseConnector::encodeDatetime( $End ) .'"';
		if( $status !== null )
			$condition .= ' AND `status` = "'. $status .'"';
		$condition .= ' ORDER BY `begin` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Gibt alle Bookings eines bestimmten Tages zurück
	 * @param \DateTime $Day
	 * @param boolean $onlyConfirmed
	 * @return array Array von Booking-Instanzen
	 * @api
	 */
	public static function getBookingsAtDay( \DateTime $Day, $onlyConfirmed=false ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$date = $DatabaseConnector::encodeDate( $Day );
		$condition = '`begin` <= "'. $date .'"';
		$condition .= ' AND `end` >= "'. $date .'"';
		if( $onlyConfirmed )
			$condition .= ' AND `status` = "booked"';
		$condition .= ' ORDER BY `begin` ASC';
		return self::getAll( $condition );
	}


	/** Gibt alle zukünftigen Bookings zurück
	 * @param int $limit
	 * @return array Array von Booking-Instanzen
	 * @api
	 */
	public static function getFutureBookings( $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`end` >= "'. $DatabaseConnector::encodeDatetime( time() ) .'"';
		$condition .= ' ORDER BY `begin` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Gibt alle vergangenen Bookings zurück
	 * @param int $limit
	 * @return array Array von Booking-Instanzen
	 * @api
	 */
	public static function getPastBookings( $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`end` <= "'. $DatabaseConnector::encodeDatetime( time() ) .'"';
		$condition .= ' ORDER BY `begin` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Gibt alle bestätigten Bookings zurück
	 * @param int $limit
	 * @return array Array von Booking-Instanzen
	 * @api
	 */
	public static function getApprovedBookings( $pastBookings=false, $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`status` = "booked"';

		if( $pastBookings )
			$condition .= ' AND `end` <= "'. $DatabaseConnector::encodeDatetime( time() ) .'"';
		else
			$condition .= ' AND `end` > "'. $DatabaseConnector::encodeDatetime( time() ) .'"';

		$condition .= ' ORDER BY `begin` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Gibt alle unbestätigten Bookings zurück
	 * @param int $limit
	 * @return array Array von Booking-Instanzen
	 * @api
	 */
	public static function getRequestedBookings( $pastBookings=false, $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`status` = "request"';

		if( $pastBookings )
			$condition .= ' AND `end` <= "'. $DatabaseConnector::encodeDatetime( time() ) .'"';
		else
			$condition .= ' AND `end` > "'. $DatabaseConnector::encodeDatetime( time() ) .'"';

		$condition .= ' ORDER BY `begin` ASC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Zählt alle Buchungen innerhalb einer Zeitspanne
	 * @param \DateTime $Begin
	 * @param \DateTime $End
	 * @param string $status
	 * @return int
	 * @api
	 */
	public static function countBookingsByTimeframe( \DateTime $Begin, \DateTime $End, $status=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`begin` >= "'. $DatabaseConnector::encodeDatetime( $Begin ) .'"';
		if( $End !== null )
			$condition .= ' AND `end` <= "'. $DatabaseConnector::encodeDatetime( $End ) .'"';
		if( $status !== null )
			$condition .= ' AND `status` = "'. $status .'"';
		$condition .= ' ORDER BY `begin` ASC';
		return self::count( $condition );
	}


	/** Zählt alle zukünftigen, bestätigten Bookings zurück
	 * @return int
	 * @api
	 */
	public static function countApprovedFutureBookings() {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`end` >= "'. $DatabaseConnector::encodeDatetime( time() ) .'"';
		$condition .= ' ORDER BY `begin` ASC';
		return self::count( $condition );
	}


/* Public methods */

	/** Gibt den Client zurück, in dessen Namen das Booking stattfindet
	 * @return Client
	 * @api
	 */
	public function getClient() {
		return Client::getById( $this->clientId );
	}


/* Private methods */

	protected function encodeBegin( $value ) {
		return \rsCore\DatabaseConnector::encodeDate( $value );
	}


	protected function decodeBegin( $value ) {
		return \rsCore\Calendar::parseDateTime( \rsCore\DatabaseConnector::decodeDate( $value ) );
	}


	protected function encodeEnd( $value ) {
		return \rsCore\DatabaseConnector::encodeDate( $value );
	}


	protected function decodeEnd( $value ) {
		return \rsCore\Calendar::parseDateTime( \rsCore\DatabaseConnector::decodeDate( $value ) );
	}


	protected function encodeIncomeDate( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


	protected function decodeIncomeDate( $value ) {
		return \rsCore\Calendar::parseDateTime( \rsCore\DatabaseConnector::decodeDatetime( $value ) );
	}


	protected function encodeNotes( $value ) {
		return strip_tags( trim( $value ) );
	}


}