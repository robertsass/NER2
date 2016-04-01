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
interface KalenderInterface {

	function extendHead( \rsCore\ProtectivePageHeadInterface $Head );
	function extendContentArea( \rsCore\Container $Container );
	
	function buildCalendar( \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Anfrage
 */
class Kalender extends Anfrage implements KalenderInterface {
	
	
	const NUMBER_YEARS = 2;


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
		
		$this->hook( 'extendHead' );
		$this->hook( 'extendContentArea' );
	}


	/** Konfiguriert den HTML-Head
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 */
	public function extendHead( \rsCore\ProtectivePageHeadInterface $Head ) {
	}


	/** Baut aus allen untergeordneten Dokumenten die Onepage zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContentArea( \rsCore\Container $Container ) {
		$CalendarContainer = $this->buildCalendar( $Container );
		$BookingForm = $this->buildBookingForm( $Container );
	}


	/** Baut den Kalender zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildCalendar( \rsCore\Container $Container ) {
		$CalendarContainer = $Container->subordinate( 'div.calendar' );
		
		$now = time();
		$Today = new \rsCore\Calendar();
		$CurDate = $Today;
		$maxYear = $CurDate->format('Y') + self::NUMBER_YEARS -1;
		
		$lastWeeknumber = 0;
		$years = range( $CurDate->format('Y'), $maxYear );
		foreach( $years as $year ) {
			
			$YearContainer = $CalendarContainer->subordinate( 'div.year' );
			$YearContainer->subordinate( 'h2', $year );
			
			$months = range( 1, 12 );
			foreach( $months as $month ) {
				
				$CurMonth = \rsCore\Calendar::parse( 'Y-m', $year .'-'. $month );
				$monthsDays = $CurMonth->format('t');
				if( $CurMonth->getTimestamp() < $now )
					continue;
					
				$MonthContainer = $YearContainer->subordinate( 'div.month' );
				$MonthContainer->subordinate( 'h3', t( $CurMonth->getMonth(2) ) );
				
				$days = range( 1, $monthsDays );
				foreach( $days as $day ) {
					
					$CurDate = \rsCore\Calendar::parse( 'Y-m-d', $year .'-'. $month .'-'. $day );
					if( $CurDate->format('W') != $lastWeeknumber ) {
						$WeekContainer = $MonthContainer->subordinate( 'div.week' );
						$lastWeeknumber = $CurDate->format('W');
						
						if( $CurDate->getWeekday() > 1 ) {
							for( $i=1; $i<$CurDate->getWeekday(); $i++ ) {
								$WeekContainer->subordinate( 'div.day.ghost > div.inner-container' );
							}
						}
					}
					
					$Bookings = self::getBookings( $CurDate );
					$available = empty( $Bookings );
					if( !$available ) {
						$mark = 'booked';
						foreach( $Bookings as $Booking ) {
							if( $Booking->begin->format('Y-m-d') == $CurDate->format('Y-m-d') )
								$mark .= '-begin';
							if( $Booking->end->format('Y-m-d') == $CurDate->format('Y-m-d') )
								$mark .= '-end';
						}
					} else {
						$mark = 'available';
					}
					
					$DayContainer = $WeekContainer->subordinate( 'div.day.'. $mark .' > div.inner-container' );
					$DayContainer->subordinate( 'span.daynumber', intval( $CurDate->getDay() ) );#->append( $CurDate->getWeekday(1) );
					
				}
				
				if( $CurDate->getWeekday() < 7 ) {
					for( $i=7; $i>$CurDate->getWeekday(); $i-- ) {
						$WeekContainer->subordinate( 'div.day.ghost > div.inner-container' );
					}
				}
				
				$lastWeeknumber = 0;
			}
		}
	}


}