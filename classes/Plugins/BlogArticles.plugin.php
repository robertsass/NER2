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
interface BlogArticlesInterface {
}


/** BlogArticlesPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class BlogArticles extends \rsCore\Plugin implements BlogArticlesInterface {


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

		$Framework->registerHook( $Plugin, 'new-article', 'api_addArticle' );
		$Framework->registerHook( $Plugin, 'list', 'api_listArticles' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveArticle' );
		$Framework->registerHook( $Plugin, 'delete-article', 'api_deleteArticle' );

		$Framework->registerHook( $Plugin, 'upload', 'api_uploadPhoto' );
		$Framework->registerHook( $Plugin, 'add-photo', 'api_addPhoto' );
		$Framework->registerHook( $Plugin, 'delete-photo', 'api_deletePhoto' );
		$Framework->registerHook( $Plugin, 'remove-photo', 'api_removePhoto' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'create,edit,remove';
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
		return self::t("Blog articles");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/blogarticles.css' );
		$Head->linkScript( '/static/js/blogarticles.js' );
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
		$Toolbar->subordinate( 'div.col-md-9 > input(button).btn btn-primary', array('data-toggle' => 'modal', 'data-target' => '#articleCreationModal', 'aria-hidden' => 'true', 'value' => self::t("New article")) );

		$Toolbar->subordinate( 'div.col-md-3', \Nightfever\NightfeverBackend::buildSitesSelector( 'site', null ) );
	}


	/** Baut die SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildSplitView( \rsCore\Container $Container ) {
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Container = $Container->subordinate( 'div.row' );
		$ListColumn = $Container->subordinate( 'div.col-md-3.list' );
		$DetailColumn = $Container->subordinate( 'div.col-md-9.details' );

		$this->buildListView( $ListColumn );
		$this->buildDetailsView( $DetailColumn );

		if( self::may('create') )
			$this->buildCreationModal( $ModalSpace );
	}


	/** Baut die Listenansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildListView( \rsCore\Container $Container ) {
		$Table = $Container->subordinate( 'table.table#articlesTable table-hover table-striped' );
		$TableBody = $Table->subordinate( 'tbody' );
	}


	/** Baut die Detailansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/blogarticles/save') );
		$DetailsView->subordinate( 'input(hidden):id' );

		$Title = $DetailsView->subordinate( 'div.title' );
		$Title->subordinate( 'h1', self::t("Details") );
		if( self::may('edit') )
			$Title->subordinate( 'button(button).btn.btn-primary.saveDetails', self::t("Save") );

		$Table = $DetailsView->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Site") );
		$Row->subordinate( 'td', \Nightfever\NightfeverBackend::buildSitesSelector( 'siteId', null ) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Date") );
		$Row->subordinate( 'td' )
			->subordinate( 'div.input-group date' )
			->subordinate( 'input.form-control(text):date', array('placeholder' => self::t("Date")) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Visibility") );
		$ButtonGroup = $Row->subordinate( 'td' )
			->subordinate( 'div.btn-group visibility', array('data-toggle' => 'buttons') );
		$ButtonGroup->subordinate( 'label.btn.btn-default', self::t("public") )
			->subordinate( 'input(radio):visibility=public' );
		$ButtonGroup->subordinate( 'label.btn.btn-default', self::t("hidden") )
			->subordinate( 'input(radio):visibility=hidden' );
		$ButtonGroup->subordinate( 'label.btn.btn-default', self::t("offline") )
			->subordinate( 'input(radio):visibility=offline' );

		foreach( \Nightfever\Nightfever::getAllowedLanguages() as $Language ) {
			$this->buildArticleVersionsForm( $DetailsView, $Language );
		}

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.deleteAlbum', self::t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.saveDetails', self::t("Save") );
	}


	/** Baut die Eingabe-Section für eine Sprache
	 * @param \rsCore\Container $Container
	 */
	public function buildArticleVersionsForm( \rsCore\Container $Container, \Brainstage\Language $Language ) {
		$Section = \Nightfever\NightfeverBackend::buildCollapsibleSection( $Container, $Language->name );
		$Section->addAttribute( 'class', 'large' );
		$Section->addAttribute( 'class', 'in' );
		$Section->parent()->addAttribute( 'class', 'expanded article-version language-'. $Language->shortCode );

		$Table = $Section->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Section") );
		$Row->subordinate( 'td > input(text).form-control:section['. $Language->shortCode .']', array('placeholder' => self::t("Section")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Title") );
		$Row->subordinate( 'td > input(text).form-control:title['. $Language->shortCode .']', array('placeholder' => self::t("Title")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Subtitle") );
		$Row->subordinate( 'td > input(text).form-control:subtitle['. $Language->shortCode .']', array('placeholder' => self::t("Subtitle")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Teaser") );
		$Row->subordinate( 'td > textarea.form-control:teaser['. $Language->shortCode .']', array('placeholder' => self::t("Teaser"), 'rows' => 5) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Text") );
		$Row->subordinate( 'td > textarea.form-control:text['. $Language->shortCode .']', array('placeholder' => self::t("Text"), 'rows' => 20) );

		return $Section;
	}


	/** Baut die Eingabemaske
	 * @param \rsCore\Container $Container
	 */
	public function buildCreationModal( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => '/brainstage/plugins/blogarticles/new-article') );
		$Modal = $Form->subordinate( 'div#articleCreationModal.modal fade', array('aria-hidden' => 'true') )
						->subordinate( 'div.modal-dialog.modal-lg > div.modal-content' );
		$ModalHead = $Modal->subordinate( 'div.modal-header' );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFoot = $Modal->subordinate( 'div.modal-footer' );

		$ModalHead->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
				->subordinate( 'span', array('aria-hidden' => 'true'), '&times;' );
		$ModalHead->subordinate( 'h1.modal-title', t("New article") );

		$ModalFoot->subordinate( 'button.btn btn-primary saveNewArticle', t("Save") );

		$Form = $ModalBody->subordinate( 'form' );

		$SelectorGroup = $Form->subordinate( 'div.row' );
		$SelectorGroup->subordinate( 'div.col-md-6 > p', \Nightfever\NightfeverBackend::buildSitesSelector( 'site', null ) );
		$SelectorGroup->subordinate( 'div.col-md-6 > p', \Nightfever\NightfeverBackend::buildLanguageSelector( 'language' ) );

/*
		$DateFieldset = $Form->subordinate( 'div.row' );
		$DateFieldset->subordinate( 'div.col-md-6 > div.form-group > div.input-group date' )
			->subordinate( 'input.form-control(text):date', array('placeholder' => t("Date")) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );
*/

		$Form->subordinate( 'p > input.form-control(text):section', array('placeholder' => self::t("Section")) );
		$Form->subordinate( 'p > input.form-control(text):title', array('placeholder' => self::t("Title")) );
		$Form->subordinate( 'p > input.form-control(text):subtitle', array('placeholder' => self::t("Subtitle")) );
		$Form->subordinate( 'p > textarea.form-control:teaser', array('placeholder' => self::t("Teaser"), 'rows' => 3) );
		$Form->subordinate( 'p > textarea.form-control:text', array('placeholder' => self::t("Text"), 'rows' => 12) );
	}


/* API Plugin */

	/** Fügt ein neues Photo ein
	 * @return boolean
	 */
	public function api_addPhoto( $params ) {
		$City = \Nightfever\City::getCityById( $params['site'] );
		$Location = \Nightfever\Location::getById( $params['location'] );
		$Photo = \Nightfever\Photo::addPhoto( $City );
		if( $Photo ) {
			$Photo->locationId = $Location->getPrimaryKeyValue();
			$Photo->start = \DateTime::createFromFormat( 'd.m.Y H:i', $params['start'] );
			$Photo->end = \DateTime::createFromFormat( 'd.m.Y H:i', $params['end'] );
			$success = $success && $Photo->adopt();
		}
		return $success ? $success : $failures;
	}


	/** Listet die Articles auf
	 * @return array
	 */
	public function api_addArticle( $params ) {
		self::throwExceptionIfNotPrivileged( 'create' );
		$Site = \Nightfever\Sites::getSiteById( postVar('site') );
		$Language = \Brainstage\Language::getLanguageByShortCode( postVar('language') );

		if( $Site ) {
			$Article = \Nightfever\BlogArticle::createArticle( $Site );
			$Version = $Article->getVersion( $Language );
			$Version->section = postVar('section');
			$Version->title = postVar('title');
			$Version->subtitle = postVar('subtitle');
			$Version->teaser = postVar('teaser');
			$Version->text = postVar('text');
			$Version->adopt();
			return $Article->getColumns();
		}
		return null;
	}


	/** Listet die Articles auf
	 * @return array
	 */
	public function api_listArticles( $params ) {
		$Site = \Nightfever\Site::getSiteById( getVar('site') );
		if( $Site ) {
			$articles = array();
			foreach( $Site->getArticles() as $Article ) {
				$articleArray = $Article->getColumns();
			#	$photoArray['beginning'] = $Photo->start;
			#	$photoArray['ending'] = $Photo->end;
				$languages = array();
				foreach( $Site->getLanguages() as $Language ) {
					$Version = $Article->getVersion( $Language );
					$languages[ $Language->shortCode ] = $Version ? $Version->getColumns() : null;
				}
				$articleArray['languages'] = $languages;
				$articles[] = $articleArray;
			}
			return $articles;
		}
		return null;
	}


	/** Speichert den Artikel
	 * @return array
	 */
	public function api_saveArticle( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$Article = \Nightfever\BlogArticle::getArticleById( postVar('id') );
		$Site = \Nightfever\Site::getSiteById( postVar('siteId') );
		if( !$Article || !$Site )
			return false;

		$fields = array('siteId', 'date', 'visibility');
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Article->set( $field, $value );
			}
		}

		$sections = postVar( 'section', array() );
		$titles = postVar( 'title', array() );
		$subtitles = postVar( 'subtitle', array() );
		$teasers = postVar( 'teaser', array() );
		$texts = postVar( 'text', array() );
		foreach( \Nightfever\Nightfever::getAllowedLanguages() as $Language ) {
			$Version = $Article->getVersion( $Language );
			$Version->section = $sections[ $Language->shortCode ];
			$Version->title = $titles[ $Language->shortCode ];
			$Version->subtitle = $subtitles[ $Language->shortCode ];
			$Version->teaser = $teasers[ $Language->shortCode ];
			$Version->text = $texts[ $Language->shortCode ];
			$Version->adopt();
		}

		return $Article->getColumns();
	}


	/** Löscht einen Artikel
	 * @return boolean
	 */
	public function api_deletePhoto( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$Photo = \Nightfever\Photo::getPhotoById( postVar('id') );
		if( $Photo )
			return $Photo->remove();
	}


}