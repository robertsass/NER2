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
interface QuotesInterface {
}


/** QuotesPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Quotes extends \rsCore\Plugin implements QuotesInterface {


	const DEFAULT_INTERVAL_SIZE = 20;


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
		$Framework->registerHook( $Plugin, 'add', 'api_addQuote' );
		$Framework->registerHook( $Plugin, 'list', 'api_listQuotes' );
		$Framework->registerHook( $Plugin, 'delete', 'api_deleteQuote' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveQuote' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'edit,add,delete';
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


/* Private methods */

	protected function getListIntervalSize() {
		return intval( getVar( 'limit', self::DEFAULT_INTERVAL_SIZE ) );
	}


	protected function getPaginationMax() {
		return ceil( $this->getFileManager()->countQuotesFiles() / $this->getListIntervalSize() );
	}


	protected function getPaginationIndex() {
		return getVar( 'page', 1 );
	}


	protected function getQuotes( $start=0, $limit=self::DEFAULT_INTERVAL_SIZE, $siteId=null ) {
		if( $siteId !== null )
			$quotes = \Nightfever\Quote::getQuotesBySites( $siteId, $limit, $start*$limit );
		else
			$quotes = \Nightfever\Quote::getQuotes( $limit, $start*$limit );
		return $quotes;
	}


/* Brainstage Plugin */

	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript('/static/js/quotes.js');
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Quotes");
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'splitView' );
		$this->buildToolbar( $Container );
		$this->buildQuoteView( $Container->subordinate( 'div.headered > div.tab-pane.active#quotesView' ) );
	}


	/** Baut die Tabbar zusammen
	 * @param \rsCore\Container $Container
	 */
	public function buildToolbar( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'header > div.row' );

		if( self::may('add') )
			$Toolbar->subordinate( 'div.col-md-9 > input(button).btn.btn-primary.newQuote', array('data-toggle' => 'modal', 'data-target' => '#quoteEntryModal', 'value' => self::t("Add quote")) );

		$Toolbar->subordinate( 'div.col-md-3', \Nightfever\NightfeverBackend::buildSitesSelector() );
	}


	/** Baut die QuoteView
	 * @param \rsCore\Container $Container
	 */
	public function buildQuoteView( \rsCore\Container $Container ) {
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Container = $Container->subordinate( 'div.row' );
		$ListColumn = $Container->subordinate( 'div.col-md-5.list' );
		$DetailColumn = $Container->subordinate( 'div.col-md-7.details' );

		$Table = $ListColumn->subordinate( 'table#quotesTable.table table-hover table-striped' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', self::t("Author") );
		$Row->subordinate( 'th', self::t("Date") );
		$Row->subordinate( 'th', self::t("Language") );
		$TableBody = $Table->subordinate( 'tbody' );

		$this->buildQuoteDetailsView( $DetailColumn );
		if( self::may('add') )
			$this->buildQuoteEntryModal( $ModalSpace );
	}


	/** Baut die Detailansicht eines Nutzers
	 * @param \rsCore\Container $Container
	 */
	public function buildQuoteDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/quotes/save') );
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
		$Row->subordinate( 'th', self::t("Language") );
		$Row->subordinate( 'td', \Nightfever\NightfeverBackend::buildLanguageSelector( 'language' ) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Creation date") );
		$Row->subordinate( 'td.creationDate' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Author") );
		$Row->subordinate( 'td > input(text).form-control:author', array('placeholder' => self::t("Author")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Quote") );
		$Row->subordinate( 'td > textarea.form-control:text', array('placeholder' => self::t("Quote")) );

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.removeQuote', self::t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.saveDetails', self::t("Save") );
	}


	/** Baut das Modal des Nutzer-Anlege-Formulars
	 * @param \rsCore\Container $Container
	 */
	public function buildQuoteEntryModal( \rsCore\Container $Container ) {
		$Modal = $Container->subordinate( 'div.modal.fade#quoteEntryModal', array('aria-hidden' => 'true', 'role' => 'dialog') )
			->subordinate( 'form', array('action' => '/brainstage/plugins/quotes/add') )
			->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalHeader = $Modal->subordinate( 'div.modal-header' );
		$ModalHeader->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
			->subordinate( 'span', '&times;' );
		$ModalHeader->subordinate( 'h1.modal-title', self::t("Add quote") );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFooter = $Modal->subordinate( 'div.modal-footer' );
		$ModalFooter->subordinate( 'button(button).btn.btn-default', array('data-dismiss' => 'modal'), self::t("Cancel") );
		$ModalFooter->subordinate( 'button(button).btn.btn-primary.saveNewQuote', self::t("Save") );

		$ModalBody->subordinate( 'p', \Nightfever\NightfeverBackend::buildSitesSelector() );
		$ModalBody->subordinate( 'p', \Nightfever\NightfeverBackend::buildLanguageSelector( 'language' ) );

		$ModalBody->subordinate( 'p > input(text).form-control:author', array('placeholder' => self::t("Author")) );
		$ModalBody->subordinate( 'p > textarea.form-control:text', array('placeholder' => self::t("Quote")) );
	}


/* API Plugin */

	/** Listet alle Zitate auf
	 * @return array
	 */
	public function api_listQuotes( $params ) {
		self::throwExceptionIfNotAuthorized();
		$start = valueByKey( $params, 'start', 0 );
		$limit = null; // valueByKey( $params, 'limit', self::DEFAULT_INTERVAL_SIZE );
		$siteId = valueByKey( $params, 'site' );
		$quotes = array();
		foreach( $this->getQuotes( $start, $limit, $siteId ) as $Quote ) {
			$columns = $Quote->getColumns();
			$columns['date'] = $Quote->date->format( t('Y-m-d', 'Date with full year') );
			$quotes[] = $columns;
		}
		return $quotes;
	}


	/** Speichert das Zitat
	 * @return array
	 */
	public function api_saveQuote( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$quoteId = postVar( 'id', null );
		$Quote = \Nightfever\Quote::getQuoteById( $quoteId );
		if( !$Quote )
			return false;

		$fields = array('text', 'author', 'language', 'siteId');
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Quote->set( $field, $value );
			}
		}

		$Language = \Brainstage\Language::getLanguageInstance( postVar('language') );
		if( $Language )
			$Quote->language = $Language->shortCode;

		$columns = $Quote->getColumns();
		return $columns;
	}


	/** Erstellt ein Zitat
	 * @return array
	 */
	public function api_addQuote( $params ) {
		self::throwExceptionIfNotPrivileged( 'add' );

		$Site = \Nightfever\Sites::getSiteById( postVar('site') );
		if( !$Site )
			throw new \Exception( "Quotes need to be assigned to a valid Site." );
		$Quote = \Nightfever\Quote::addQuote( $Site, postVar('language') );
		$Quote->text = postVar('text');
		$Quote->author = postVar('author');
		$Quote->adopt();

		$columns = $Quote->getColumns();
		return $columns;
	}


	/** Löscht ein Zitat
	 * @return boolean
	 */
	public function api_deleteQuote( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$Quote = \Nightfever\Quote::getQuoteById( postVar('id') );
		if( $Quote )
			return $Quote->remove();
	}


}