<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Plugins;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 * @internal
 */
interface LocationsInterface {
}


/** LocationsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Locations extends \rsCore\Plugin implements LocationsInterface {


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
		$Framework->registerHook( $Plugin, 'add', 'api_addLocation' );
		$Framework->registerHook( $Plugin, 'list', 'api_listLocations' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveLocation' );
		$Framework->registerHook( $Plugin, 'delete', 'api_deleteLocation' );
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


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Locations");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript( '/static/js/locations.js' );
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
		$Toolbar->subordinate( 'div.col-md-9 > input(button).btn btn-primary', array('data-toggle' => 'modal', 'data-target' => '#locationCreationModal', 'aria-hidden' => 'true', 'value' => t("Add location")) );

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
		$Table = $Container->subordinate( 'table.table#locationTable table-hover table-striped' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', t("Name") );
		$TableBody = $Table->subordinate( 'tbody' );
	}


	/** Baut die Detailansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/locations/save') );
		$DetailsView->subordinate( 'input(hidden):id' );

		$Title = $DetailsView->subordinate( 'div.title' );
		$Title->subordinate( 'h1', self::t("Details") );
		if( self::may('edit') )
			$Title->subordinate( 'button(button).btn.btn-primary.saveDetails', self::t("Save") );

		$Table = $DetailsView->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Site") );
		$Row->subordinate( 'td', \Nightfever\NightfeverBackend::buildSitesSelector( 'cityId' ) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Name") );
		$Row->subordinate( 'td > input(text).form-control:name', array('placeholder' => self::t("Location name")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Address") );
		$Row->subordinate( 'td > input(text).form-control:address', array('placeholder' => self::t("Address")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Postal code") );
		$Row->subordinate( 'td > input(text).form-control:postalCode', array('placeholder' => self::t("Postal code")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("City") );
		$Row->subordinate( 'td > input(text).form-control:city', array('placeholder' => self::t("City")) );

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.removeLocation', self::t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.saveDetails', self::t("Save") );
	}


	/** Baut die Eingabemaske
	 * @param \rsCore\Container $Container
	 */
	public function buildCreationModal( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/locations/add') );
		$Modal = $Form->subordinate( 'div#locationCreationModal.modal fade', array('aria-hidden' => 'true') )
						->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalHead = $Modal->subordinate( 'div.modal-header' );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFoot = $Modal->subordinate( 'div.modal-footer' );

		$ModalHead->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
				->subordinate( 'span', array('aria-hidden' => 'true'), '&times;' );
		$ModalHead->subordinate( 'h1.modal-title', t("Add location") );

		$ModalFoot->subordinate( 'button.btn btn-primary save-location', t("Save") );

		$Form = $ModalBody->subordinate( 'form' );

		$Form->subordinate( 'p', \Nightfever\NightfeverBackend::buildSitesSelector() );

		$Form->subordinate( 'p > input.form-control(text):name', array('placeholder' => t("Location name")) );
		$Form->subordinate( 'p > input.form-control(text):address', array('placeholder' => t("Address")) );
		$Row = $Form->subordinate( 'div.row' );
		$Row->subordinate( 'div.col-md-6 > input.form-control(text):postalCode', array('placeholder' => t("Postal code")) );
		$Row->subordinate( 'div.col-md-6 > input.form-control(text):city', array('placeholder' => t("City")) );
	}


/* API Plugin */

	/** Fügt ein neues Location ein
	 * @return boolean
	 */
	public function api_addLocation( $params ) {
		$name = trim( $params['name'] );
		$City = \Nightfever\City::getCityById( $params['cityId'] );
		$Location = \Nightfever\Location::getLocation( $City, $name );
		if( $City && !$Location ) {
			$Location = \Nightfever\Location::addLocation( $City, $name );
			$Location->address = $params['address'];
			$Location->postalCode = $params['postalCode'];
			$Location->city = $params['city'];
			$Location->adopt();
		}
		return $Location->getColumns();
	}


	/** Listet die Locations auf
	 * @return array
	 */
	public function api_listLocations( $params ) {
		$City = \Nightfever\City::getCityById( getVar('site') );
		$locations = array();
		foreach( array_reverse( $City->getLocations() ) as $Location ) {
			$array = $Location->getColumns();
			$locations[] = $array;
		}
		return $locations;
	}


	/** Speichert Veranstaltungsdetails
	 * @return array
	 */
	public function api_saveLocation( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$Location = \Nightfever\Location::getLocationById( postVar('id') );
		if( !$Location )
			return false;

		$fields = array('cityId', 'name', 'address', 'postalCode', 'city');
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Location->set( $field, $value );
			}
		}

		return $Location->getColumns();
	}


	/** Löscht ein Location
	 * @return boolean
	 * @todo Prüfen ob das Location auch im Zuständigkeitsbereich liegt und gelöscht werden darf
	 */
	public function api_deleteLocation( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$Location = \Nightfever\Location::getLocationById( postVar('id') );
		if( $Location )
			return $Location->remove();
	}


}