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
interface AnfrageInterface {

	function extendHead( \rsCore\ProtectivePageHeadInterface $Head );
	function extendContentArea( \rsCore\Container $Container );
	
	function buildBookingForm( \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class Anfrage extends Base implements AnfrageInterface {
	
	
	const DATE_FORMAT = 'd.m.Y';
	const NOTIFICATION_SUBJECT = "Neue Buchungsanfrage";
	const NOTIFICATION_MESSAGE = "Es wurde eine neue Buchungsanfrage gestellt:\n\nKunde: *CLIENT_NAME*\nZeitraum: *FROM* - *TO*\n\nAnmerkung: *COMMENT*\n\n*BACKEND_LINK*";
	
	private $_bookingFormErrors;


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
		
		$this->hook( 'extendHead' );
		$this->hook( 'extendContentArea' );
		
		$this->_bookingFormErrors = array();
		if( postVar('arrival') )
			$this->handleBooking();
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
		$BookingForm = $this->buildBookingForm( $Container );
	}


	/** Baut das Buchungsformular zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildBookingForm( \rsCore\Container $Container ) {
		$Container->subordinate( 'h1', "Buchungsanfrage" );
		$Form = $Container->subordinate( 'form.form-horizontal', array('method' => 'post') );
		
		if( !empty( $this->_bookingFormErrors ) ) {
			foreach( $this->_bookingFormErrors as $text ) {
				$Form->subordinate( 'div.alert.alert-danger', $text );
			}
		}
		
		$Form->subordinate( 'h2', "Kontaktdaten" );
		
		$Row = $Form->subordinate( 'div.row' );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "Vorname" .':' )->parent()
			->subordinate( 'div.col-md-8 > input(text).form-control:firstname' );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "Nachname" .':' )->parent()
			->subordinate( 'div.col-md-8 > input(text).form-control:lastname' );
			
		$Row = $Form->subordinate( 'div.row' );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "E-Mail" .':' )->parent()
			->subordinate( 'div.col-md-8 > input(text).form-control:email' );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "Telefon" .':' )->parent()
			->subordinate( 'div.col-md-8 > input(text).form-control:telephone' );
		
		$Form->subordinate( 'h2', "Reisezeitraum" );
		
		$placeholderAttr = array('placeholder' => 'tt.mm.jjjj');
		$Row = $Form->subordinate( 'div.row' );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "Ankunft" .':' )->parent()
			->subordinate( 'div.col-md-8 > input(text).form-control:arrival', $placeholderAttr );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "Abreise" .':' )->parent()
			->subordinate( 'div.col-md-8 > input(text).form-control:departure', $placeholderAttr );
		
		$Form->subordinate( 'h2', "Sonstiges" );
			
		$Row = $Form->subordinate( 'div.row' );
		$Row->subordinate( 'div.col-md-6.form-group > label' )
			->subordinate( 'div.col-md-4.control-label', "Anmerkungen" .':' )->parent()
			->subordinate( 'div.col-md-8 > textarea.fullwidth:comment', array('cols' => 6) );
			
		$Row = $Form->subordinate( 'div.row' );
		$Row->subordinate( 'div.col-md-6.form-group' )
			->subordinate( 'div.col-md-4' )->parent()
			->subordinate( 'div.col-md-8 > button(submit).btn.btn-lg.btn-success', "Unverbindlich anfragen" )->subordinate( 'span.glyphicon glyphicon-menu-right' );
	}


/* Private methods */

	/** Gibt alle Buchungen eines Tages zurück
	 *
	 * @param \rsCore\Calendar $Day
	 * @return array
	 */
	protected static function getBookings( \rsCore\Calendar $Day ) {
		return \Site\Booking::getBookingsAtDay( $Day->getDateTime(), true );
	}


	/** Prüft, ob an einem Tag Buchungen vorliegen
	 *
	 * @param \rsCore\Calendar $Day
	 * @return boolean
	 */
	protected static function isAvailable( \rsCore\Calendar $Day ) {
		$bookings = self::getBookings( $Day );
		$isEmpty = empty( $bookings );
		return $isEmpty;
	}


	/** Handling des Booking-Formulars
	 *
	 * @return void
	 */
	protected function handleBooking() {
		if( !empty( $_POST ) ) {
			$valid = true;
			
			$fields = array('firstname' => "Vorname", 'lastname' => "Nachname", 'email' => "E-Mail", 'telephone' => "Telefon");
			foreach( $fields as $field => $label ) {
				if( !isset( $_POST[ $field ] ) || trim( $_POST[ $field ] ) == '' ) {
					$valid = false;
					$this->_bookingFormErrors[] = "Das Feld ". $label ." ist nicht korrekt ausgefüllt. Bitte überprüfen Sie Ihre Eingabe!";
				}
			}
			
			$fields = array('arrival' => "Ankunft", 'departure' => "Abreise");
			foreach( $fields as $field => $label ) {
				if( !isset( $_POST[ $field ] ) || \rsCore\Calendar::parse( self::DATE_FORMAT, $_POST[ $field ] ) == null ) {
					$valid = false;
					$this->_bookingFormErrors[] = "Das ". $label ."-Datum ist nicht korrekt angegeben. Bitte überprüfen Sie Ihre Eingabe!";
				}
			}

			if( $valid ) {
				//... save
				$Client = \Site\Client::addClient( $_POST['email'] );
				if( $Client ) {
					if( $Client->lastname != postVar('lastname') ) {
						$Client->lastname = postVar('lastname');
						$Client->firstname = postVar('firstname');
						$Client->telephone = postVar('telephone');
					}
					$Begin = \rsCore\Calendar::parse( self::DATE_FORMAT, $_POST['arrival'] );
					$End = \rsCore\Calendar::parse( self::DATE_FORMAT, $_POST['departure'] );
					$Booking = \Site\Booking::addBooking( $Client, $Begin->getDateTime(), $End->getDateTime() );
					if( $Booking ) {
						//... @todo eMail-Notification senden
						$NotificationReceiverSetting = \Brainstage\Setting::getMixedSetting( 'Booking_Notification_Receiver', true );
						$notificationReceiverAddress = $NotificationReceiverSetting->value;
						
						$NotificationSenderSetting = \Brainstage\Setting::getMixedSetting( 'Booking_Notification_Sender', true );
						$notificationSenderAddress = $NotificationSenderSetting->value;
						
						$notificationMessage = self::NOTIFICATION_MESSAGE;
						$notificationMessage = str_replace( '*CLIENT_NAME*', $Client->lastname .', '. $Client->firstname, $notificationMessage );
						$notificationMessage = str_replace( '*FROM*', $Begin->format( self::DATE_FORMAT ), $notificationMessage );
						$notificationMessage = str_replace( '*TO*', $End->format( self::DATE_FORMAT ), $notificationMessage );
						$notificationMessage = str_replace( '*COMMENT*', postVar('comment'), $notificationMessage );
						$notificationMessage = str_replace( '*BACKEND_LINK*', \Brainstage\Brainstage::getBrainstageUrl(), $notificationMessage );
						
						mail(
							$notificationReceiverAddress,
							self::NOTIFICATION_SUBJECT,
							$notificationMessage,
							'FROM: '. $notificationSenderAddress
						);
						return true;
					}
					else
						$this->_bookingFormErrors[] = "Es ist ein Fehler beim Speichern des Buchungsdatensatzes aufgetreten.";
				}
				else
					$this->_bookingFormErrors[] = "Es ist ein Fehler beim Speichern des Kundendatensatzes aufgetreten.";
			}
			else
				return false;
		}
	}


}