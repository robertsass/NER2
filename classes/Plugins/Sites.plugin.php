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
interface SitesInterface {
}


/** SitesPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Sites extends \rsCore\Plugin implements SitesInterface {


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
		$Framework->registerHook( $Plugin, 'add', 'api_addSite' );
		$Framework->registerHook( $Plugin, 'list', 'api_listSites' );
		$Framework->registerHook( $Plugin, 'delete', 'api_deleteSite' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveSite' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return array(
			'edit' => 'boolean',
			'add' => 'boolean',
			'delete' => 'boolean',
			'sites' => 'integers',
			'allSites' => 'boolean'
		);
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
		return ceil( $this->getFileManager()->countSitesFiles() / $this->getListIntervalSize() );
	}


	protected function getPaginationIndex() {
		return getVar( 'page', 1 );
	}


	/** Baut ein Select zur Auswahl des Site-Parents
	 * @param string $formName
	 * @return \rsCore\Container
	 * @api
	 */
	public static function buildSiteParentSelector( $formName="parent" ) {
		$RootSite = \Nightfever\Sites::getNestedSet()->getRoot();
		$Selector = new \rsCore\Container( 'select.selectize', array('name' => $formName) );
		$Selector->subordinate( 'option', array('value' => $RootSite->getPrimaryKeyValue()), $RootSite->name );

		$Group = $Selector->subordinate( 'optgroup', array('label' => self::t("Countries")) );
		foreach( \Nightfever\Sites::getCountries() as $Site )
			$Group->subordinate( 'option', array('value' => $Site->getPrimaryKeyValue()), $Site->name );
		return $Selector;
	}


	/** Baut ein Select zur Auswahl der Role
	 * @param string $formName
	 * @return \rsCore\Container
	 * @api
	 */
	public static function buildSiteRoleSelector( $formName="role" ) {
		$roles = array('city', 'country', 'special', 'other');
		$Selector = new \rsCore\Container( 'select.selectize', array('name' => $formName) );
		foreach( $roles as $role ) {
			$Selector->subordinate( 'option', array('value' => $role), $role );
		}
		return $Selector;
	}


/* Brainstage Plugin */

	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript('/static/js/sites.js');
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Sites");
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'splitView' );
		$Header = $Container->subordinate( 'header > div.row' );

		$this->buildTabBar( $Header->subordinate( 'div.col-md-9' ) );
		$this->buildTabViews( $Container );

		$RightCol = $Header->subordinate( 'div.col-md-3' );
		if( self::may('add') )
			$RightCol->subordinate( 'input(button).btn.btn-default.newSite', array('data-toggle' => 'modal', 'data-target' => '#siteEntryModal', 'value' => self::t("New site")) );
	}


	/** Baut die Tabbar zusammen
	 * @param \rsCore\Container $Container
	 */
	public function buildTabBar( \rsCore\Container $Container ) {
		$tabAttr = array('role' => 'tab', 'data-toggle' => 'tab');
		$Bar = $Container->subordinate( 'ul.nav.nav-tabs' );
		if( self::may('edit') )
			$Bar->subordinate( 'li > a', array_merge($tabAttr, array('data-target' => '#sitesView')), self::t("Sites") );
	}


	/** Baut die den Tabs zugehörigen Views
	 * @param \rsCore\Container $Container
	 */
	public function buildTabViews( \rsCore\Container $Container ) {
		$Container = $Container->subordinate( 'div.headered.tab-content' );
		$this->buildSiteView( $Container->subordinate( 'div.tab-pane.active#sitesView' ) );
	}


	/** Baut die SiteView
	 * @param \rsCore\Container $Container
	 */
	public function buildSiteView( \rsCore\Container $Container ) {
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Container = $Container->subordinate( 'div.row' );
		$ListColumn = $Container->subordinate( 'div.col-md-5.list' );
		$DetailColumn = $Container->subordinate( 'div.col-md-7.details' );

		$Table = $ListColumn->subordinate( 'table#quotesTable.table table-hover table-striped' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', self::t("Name") );
		$Row->subordinate( 'th', self::t("Role") );
		$TableBody = $Table->subordinate( 'tbody' );

		$this->buildSiteDetailsView( $DetailColumn );

		if( self::may('add') )
			$this->buildSiteEntryModal( $ModalSpace );
	}


	/** Baut die Detailansicht eines Nutzers
	 * @param \rsCore\Container $Container
	 */
	public function buildSiteDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/sites/save') );
		$DetailsView->subordinate( 'input(hidden):id' );

		$Title = $DetailsView->subordinate( 'div.title' );
		$Title->subordinate( 'h1', self::t("Details") );
		if( self::may('edit') )
			$Title->subordinate( 'button(button).btn.btn-primary.saveDetails', self::t("Save") );

		$Table = $DetailsView->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Name") );
		$Row->subordinate( 'td > input(text).form-control:name', array('placeholder' => self::t("Name")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Short name") );
		$Row->subordinate( 'td > input(text).form-control:shortname', array('placeholder' => self::t("Short name")) );

		$this->buildSiteLanguageForm( $DetailsView );

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.removeSite', self::t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.saveDetails', self::t("Save") );
	}


	/** Baut die Eingabe-Section für eine Sprache
	 * @param \rsCore\Container $Container
	 */
	public function buildSiteLanguageForm( \rsCore\Container $Container ) {
		$Section = \Nightfever\NightfeverBackend::buildCollapsibleSection( $Container, self::t("Languages") );

		$Table = $Section->subordinate( 'table.table.table-striped' );
		$TableBody = $Table->subordinate( 'tbody' );

		foreach( \Brainstage\Language::getLanguages() as $Language ) {
			$Row = $TableBody->subordinate( 'tr' );
			$Row->subordinate( 'td > input(checkbox)', array('name' => 'languages[]', 'value' => $Language->shortCode) );
			$Row->subordinate( 'td', $Language->name );
		}

		return $Section;
	}


	/** Baut das Modal des Nutzer-Anlege-Formulars
	 * @param \rsCore\Container $Container
	 */
	public function buildSiteEntryModal( \rsCore\Container $Container ) {
		$Modal = $Container->subordinate( 'div.modal.fade#siteEntryModal', array('aria-hidden' => 'true', 'role' => 'dialog') )
			->subordinate( 'form', array('action' => '/brainstage/plugins/sites/add') )
			->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalHeader = $Modal->subordinate( 'div.modal-header' );
		$ModalHeader->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
			->subordinate( 'span', '&times;' );
		$ModalHeader->subordinate( 'h1.modal-title', self::t("New site") );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFooter = $Modal->subordinate( 'div.modal-footer' );
		$ModalFooter->subordinate( 'button(button).btn.btn-default', array('data-dismiss' => 'modal'), self::t("Cancel") );
		$ModalFooter->subordinate( 'button(button).btn.btn-primary.saveNewSite', self::t("Save") );

		$ModalBody->subordinate( 'p', self::buildSiteParentSelector( 'parent' ) );
		$ModalBody->subordinate( 'p', self::buildSiteRoleSelector( 'role' ) );
		$ModalBody->subordinate( 'p > input(text).form-control:name', array('placeholder' => self::t("Name")) );
		$ModalBody->subordinate( 'p > input(text).form-control:shortname', array('placeholder' => self::t("Short name")) );
	}


/* API Plugin */

	/** Listet alle Nutzer auf
	 * @return array
	 */
	public function api_listSites( $params ) {
		self::throwExceptionIfNotAuthorized();
		$sites = array();
		$condition = '`'. \rsCore\DatabaseNestedSet::FIELDNAME_LEFT .'` != "0" ORDER BY `role`,`name`';
		foreach( \Nightfever\Sites::getAllSites( $condition ) as $Site ) {
			$Site = \Nightfever\Sites::getSiteById( $Site->getPrimaryKeyValue() );

/*
			$Group = \Brainstage\Group::getGroupByName( $Site->shortname );
			if( !$Group )
				$Group = \Brainstage\Group::getGroupByName( $Site->name );
			if( !$Group )
				$Group = \Brainstage\Group::getGroupByName( ucfirst($Site->role) .': '. trim( $Site->name ) );
			if( !$Group )
				$Group = \Brainstage\Group::addGroup( $Site->name, false );
			if( $Group ) {
				$Group->name = ucfirst($Site->role) .': '. trim( $Site->name );
				$Group->adopt();
				\Brainstage\GroupRight::addRight( 'Plugins/Sites:sites', $Site->getPrimaryKeyValue(), $Group, false );
				\Brainstage\GroupRight::addRight( 'Brainstage/Plugins/Documents:roots', $Site->documentId, $Group, false );
			}
*/

			$columns = $Site->getColumns();
			$languages = array();
			foreach( $Site->getLanguages() as $Language )
				$languages[] = $Language->shortCode;
			$columns['languages'] = $languages;
			$sites[] = $columns;
		}
		return $sites;
	}


	/** Speichert das Zitat
	 * @return array
	 */
	public function api_saveSite( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$Site = \Nightfever\Sites::getSiteById( postVar('id') );
		if( !$Site )
			return false;

		$fields = array('name', 'shortname');
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Site->set( $field, $value );
			}
		}

		if( $Site->role != 'city' ) {
			$Site->removeLanguages();
			foreach( postVar( 'languages', array() ) as $shortCode ) {
				$Site->addLanguage( $shortCode );
			}
		}

		$columns = $Site->getColumns();
		return $columns;
	}


	/** Erstellt ein Zitat
	 * @return array
	 */
	public function api_addSite( $params ) {
		self::throwExceptionIfNotPrivileged( 'add' );

		$Site = \Nightfever\Sites::getSiteByShortname( trim( $params['shortname'] ) );
		if( $Site )
			throw new \Exception( "Short name is already used by another site." );

		$Site = \Nightfever\Sites::addSite( $params['parent'], trim( $params['shortname'] ), $params['role'] );
		$Site->name = trim( $params['name'] );
		$Site->adopt();

		$Document = $Site->getDocument();
		if( $Document ) {
			$usersLanguages = array_keys( \rsCore\Useragent::detectLanguages() );
			$language = \rsCore\Localization::extractLanguageCode( current( $usersLanguages ) );
			$DocumentVersion = $Document->newVersion( $language );
			$DocumentVersion->name = trim( $params['name'] );
			$DocumentVersion->adopt();
		}

		$groupName = ucfirst( $Site->role ) .': '. $Site->name;
		$Group = \Brainstage\Group::getGroupByName( $groupName );
		if( !$Group )
			$Group = \Brainstage\Group::addGroup( $groupName, false );
		if( $Group ) {
			\Brainstage\GroupRight::addRight( 'Plugins/Sites:sites', $Site->getPrimaryKeyValue(), $Group, false );
			\Brainstage\GroupRight::addRight( 'Brainstage/Plugins/Documents:roots', $Site->documentId, $Group, false );
		}

		$columns = $Site->getColumns();
		return $columns;
	}


	/** Löscht ein Zitat
	 * @return boolean
	 */
	public function api_deleteSite( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$Site = \Nightfever\Sites::getSiteById( postVar('id') );
		if( $Site )
			return $Site->remove();
	}


}