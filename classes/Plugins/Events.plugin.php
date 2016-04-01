<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Plugins;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface EventsInterface {
}


/** EventsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Events extends \rsCore\Plugin implements EventsInterface {


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function brainstageRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'buildHead' );
		$Framework->registerHook( $Plugin, 'buildBody' );
		$Framework->registerHook( $Plugin, 'getNavigatorItem' );
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'add', 'api_addEvent' );
		$Framework->registerHook( $Plugin, 'list', 'api_listEvents' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveEvent' );
		$Framework->registerHook( $Plugin, 'delete', 'api_deleteEvent' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'add,edit,delete';
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
		parent::init();
	}


	/** Baut ein Select zur Auswahl der Location
	 * @param string $formName
	 * @return \rsCore\Container
	 * @api
	 */
	public static function buildLocationSelector( $formName="location" ) {
		$cities = array();
		foreach( \Nightfever\Nightfever::getAllowedSites() as $Site )
			if( $Site->role == 'city' )
				$cities[] = \Nightfever\City::getCityById( $Site->getPrimaryKeyValue() );
		$Selector = new \rsCore\Container( 'select.selectize', array('name' => $formName) );
		foreach( $cities as $City ) {
		#	$Country = $City->getCountry();
			$Group = $Selector->subordinate( 'optgroup', array('label' => $City->name) );
			foreach( \Nightfever\Location::getLocationsByCity( $City ) as $Location ) {
				$Group->subordinate( 'option', array('value' => $Location->getPrimaryKeyValue()), $Location->name );
			}
		}
		return $Selector;
	}


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Events");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/events.css' );
		$Head->linkScript( '/static/js/events.js' );
		$format = \Nightfever\Nightfever::convertDateformatToMomentjsFormat( t('Y-m-d H:i', 'Date and Time: full year, without seconds') );
		$Head->addOther( new \rsCore\Container( 'script', 'var localeDateTimeFormat = "'. $format .'";' ) );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'splitView' );
		$this->buildToolbar( $Container );
		$this->buildSplitView( $Container->subordinate( 'div.headered' ) );
	}


	/** Baut die Toolbar
	 * @param \rsCore\Container $Container
	 */
	public function buildToolbar( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'header > div.row' );
		$Toolbar->subordinate( 'div.col-md-9 > input(button).btn btn-primary', array('data-toggle' => 'modal', 'data-target' => '#eventCreationModal', 'aria-hidden' => 'true', 'value' => t("Add event")) );

		$Toolbar->subordinate( 'div.col-md-3', \Nightfever\NightfeverBackend::buildSitesSelector() );
	}


	/** Baut die SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildSplitView( \rsCore\Container $Container ) {
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Container = $Container->subordinate( 'div.row' );
		$ListColumn = $Container->subordinate( 'div.col-md-5.list' );
		$DetailColumn = $Container->subordinate( 'div.col-md-7.details' );

		$this->buildListView( $ListColumn );
		$this->buildDetailsView( $DetailColumn );

		if( self::may('add') )
			$this->buildCreationModal( $ModalSpace );
	}


	/** Baut die Listenansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildListView( \rsCore\Container $Container ) {
		$Table = $Container->subordinate( 'table.table#eventTable table-hover table-striped' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', t("Beginning") );
		$Row->subordinate( 'th', t("Location") );
		$TableBody = $Table->subordinate( 'tbody' );
	}


	/** Baut die Detailansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/events/save') );
		$DetailsView->subordinate( 'input(hidden):id' );

/*
		$Title = $DetailsView->subordinate( 'div.title' );
		$Title->subordinate( 'h1', self::t("Details") );
		if( self::may('edit') )
			$Title->subordinate( 'button(button).btn.btn-primary.saveDetails', self::t("Save") );
*/

		$Table = $DetailsView->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Site") );
		$Row->subordinate( 'td', \Nightfever\NightfeverBackend::buildSitesSelector( 'siteId' ) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Location") );
		$Row->subordinate( 'td', self::buildLocationSelector( 'locationId' ) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Beginning") );
		$Row->subordinate( 'td' )
			->subordinate( 'div.input-group date' )
			->subordinate( 'input.form-control(text):start', array('placeholder' => self::t("Beginning")) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Ending") );
		$Row->subordinate( 'td' )
			->subordinate( 'div.input-group date' )
			->subordinate( 'input.form-control(text):end', array('placeholder' => self::t("Ending")) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );

		foreach( \Nightfever\Nightfever::getAllowedLanguages() as $Language ) {
			$this->buildEventMetaForm( $DetailsView, $Language );
		}

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.removeEvent', self::t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.saveDetails', self::t("Save") );
	}


	/** Baut die Eingabe-Section für eine Sprache
	 * @param \rsCore\Container $Container
	 */
	public function buildEventMetaForm( \rsCore\Container $Container, \Brainstage\Language $Language ) {
		$Section = \Nightfever\NightfeverBackend::buildCollapsibleSection( $Container, $Language->name );
		$Section->addAttribute( 'class', 'in' );
		$Section->parent()->addAttribute( 'class', 'expanded event-meta language-'. $Language->shortCode );

		$Table = $Section->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Title") );
		$Row->subordinate( 'td > input(text).form-control:title['. $Language->shortCode .']', array('placeholder' => self::t("Title")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Description") );
		$Row->subordinate( 'td > textarea.form-control:description['. $Language->shortCode .']', array('placeholder' => self::t("Description")) );

		return $Section;
	}


	/** Baut die Eingabemaske
	 * @param \rsCore\Container $Container
	 */
	public function buildCreationModal( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/events/add') );
		$Modal = $Form->subordinate( 'div#eventCreationModal.modal fade', array('aria-hidden' => 'true') )
						->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalHead = $Modal->subordinate( 'div.modal-header' );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFoot = $Modal->subordinate( 'div.modal-footer' );

		$ModalHead->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
				->subordinate( 'span', array('aria-hidden' => 'true'), '&times;' );
		$ModalHead->subordinate( 'h1.modal-title', t("Add event") );

		$ModalFoot->subordinate( 'button.btn btn-primary save-event', t("Save") );

		$Form = $ModalBody->subordinate( 'form' );

		$Form->subordinate( 'p', \Nightfever\NightfeverBackend::buildSitesSelector( 'site', 'city', true, false ) );
		$Form->subordinate( 'p', self::buildLocationSelector() );

		$DateFieldset = $Form->subordinate( 'div.row' );
		$DateFieldset->subordinate( 'div.col-md-6 > div.form-group > div.input-group date' )
			->subordinate( 'input.form-control(text):start', array('placeholder' => t("Beginning")) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );
		$DateFieldset->subordinate( 'div.col-md-6 > div.form-group > div.input-group date' )
			->subordinate( 'input.form-control(text):end', array('placeholder' => t("Ending")) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );
	}


/* API Plugin */

	/** Fügt ein neues Event ein
	 * @return boolean
	 */
	public function api_addEvent( $params ) {
		$City = \Nightfever\City::getCityById( $params['site'] );
		$Location = \Nightfever\Location::getById( $params['location'] );
		$Event = \Nightfever\Event::addEvent( $City );
		if( $Event ) {
			$Event->locationId = $Location->getPrimaryKeyValue();
			$Event->start = \DateTime::createFromFormat( t('Y-m-d H:i', 'Date and Time: full year, without seconds'), $params['start'] );
			$Event->end = \DateTime::createFromFormat( t('Y-m-d H:i', 'Date and Time: full year, without seconds'), $params['end'] );
			$success = $success && $Event->adopt();
		}
		return $success ? $success : $failures;
	}


	/** Listet die Events auf
	 * @return array
	 */
	public function api_listEvents( $params ) {
		$City = \Nightfever\City::getCityById( getVar('site') );
		$events = array();
		foreach( array_reverse( $City->getEvents() ) as $Event ) {
			$eventArray = $Event->getColumns();

			$eventArray['start'] = $Event->start->format( t('Y-m-d H:i', 'Date and Time: full year, without seconds') );
			$eventArray['end'] = $Event->end->format( t('Y-m-d H:i', 'Date and Time: full year, without seconds') );

			$languages = array();
			$Site = $Event->getSite();
			if( $Site ) {
				foreach( $Site->getLanguages() as $Language ) {
					$Meta = $Event->getMeta( $Language );
					$languages[ $Language->shortCode ] = $Meta ? $Meta->getColumns() : null;
				}
			}
			$eventArray['languages'] = $languages;

			$Location = $Event->getLocation();
			$eventArray['locationName'] = $Location ? $Location->name : '';

			$events[] = $eventArray;
		}
		return $events;
	}


	/** Speichert Veranstaltungsdetails
	 * @return array
	 */
	public function api_saveEvent( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$Event = \Nightfever\Event::getEventById( postVar('id') );
		if( !$Event )
			return false;

		$fields = array('siteId', 'locationId');
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Event->set( $field, $value );
			}
		}
		$Event->start = \DateTime::createFromFormat( t('Y-m-d H:i', 'Date and Time: full year, without seconds'), $_POST['start'] );
		$Event->end = \DateTime::createFromFormat( t('Y-m-d H:i', 'Date and Time: full year, without seconds'), $_POST['end'] );

		$titles = postVar( 'title', array() );
		$descriptions = postVar( 'description', array() );
		foreach( \Nightfever\Nightfever::getAllowedLanguages() as $Language ) {
			$Meta = $Event->getMeta( $Language );
			$Meta->title = $titles[ $Language->shortCode ];
			$Meta->description = $descriptions[ $Language->shortCode ];
			$Meta->adopt();
		}

		return $Event->getColumns();
	}


	/** Löscht ein Event
	 * @return boolean
	 * @todo Prüfen ob das Event auch im Zuständigkeitsbereich liegt und gelöscht werden darf
	 */
	public function api_deleteEvent( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$Event = \Nightfever\Event::getEventById( postVar('id') );
		if( $Event )
			return $Event->remove();
	}


}