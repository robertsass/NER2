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
interface FeedbacksInterface {
}


/** FeedbacksPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Feedbacks extends \rsCore\Plugin implements FeedbacksInterface {


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
		$Framework->registerHook( $Plugin, 'add', 'api_addFeedback' );
		$Framework->registerHook( $Plugin, 'list', 'api_listFeedbacks' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveFeedback' );
		$Framework->registerHook( $Plugin, 'delete', 'api_deleteFeedback' );
		$Framework->registerHook( $Plugin, 'badge_count', 'api_badgeCount' );
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


	/** Baut die Rückgabe der List-API-Abfrage zusammen
	 */
	protected static function buildFeedbacksList( array $Feedbacks ) {
		$list = array();
		foreach( $Feedbacks as $Feedback ) {
			$array = $Feedback->getColumns();
			$Client = $Feedback->getClient();

			$array['date'] = $Feedback->date->format( 'Y-m-d' );
			$array['comment_extract'] = \rsCore\StringUtils::getTeaser( $Feedback->comment, 100, 20, 1 );

			$list[] = $array;
		}
		return $list;
	}


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Feedbacks");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/feedbacks.css' );
		$Head->linkScript( '/static/js/feedbacks.js' );
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
		$this->buildTabBar( $Toolbar->subordinate( 'div.col-md-5' ) );
		$Toolbar->subordinate( 'div.col-md-7 > input(button).btn btn-primary', array('data-toggle' => 'modal', 'data-target' => '#feedbackCreationModal', 'aria-hidden' => 'true', 'value' => t("Add feedback")) );
	}


	/** Baut die Tabbar zusammen
	 * @param \rsCore\Container $Container
	 */
	public function buildTabBar( \rsCore\Container $Container ) {
		$tabAttr = array('role' => 'tab', 'data-toggle' => 'tab');
		$Bar = $Container->subordinate( 'ul.nav.nav-tabs' );

		$tabs = array(
			'all'		=> t("All feedbacks"),
			'public'	=> t("Public feedbacks"),
		);
		foreach( $tabs as $param => $title ) {
			$attr = array_merge( $tabAttr, array('data-api-parameters' => $param) );
			$Bar->subordinate( 'li > a', $attr, $title );
		}
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
		$Table = $Container->subordinate( 'table.table#feedbackTable table-hover table-striped' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', t("Date") );
		$Row->subordinate( 'th', t("Author") );
		$Row->subordinate( 'th', t("Comment") );
		$TableBody = $Table->subordinate( 'tbody' );
	}


	/** Baut die Detailansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => 'save') );
		$DetailsView->subordinate( 'input(hidden):id' );

/*
		$Title = $DetailsView->subordinate( 'div.title' );
		$Title->subordinate( 'h1', self::t("Details") );
		if( self::may('edit') )
			$Title->subordinate( 'button(button).btn.btn-primary.saveDetails', self::t("Save") );
*/

		$Table = $DetailsView->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

/*
		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Client") );
		$ClientSelector = $Row->subordinate( 'td > select.selectize:clientId', array('placeholder' => t("Select client")) );
		$ClientSelector->subordinate( 'option', t("Select client") );
		foreach( \Site\Client::getClients() as $Client ) {
			$ClientSelector->subordinate( 'option', array('value' => $Client->getPrimaryKeyValue()), $Client->lastname .', '. $Client->firstname );
		}
*/

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Date") );
		$Row->subordinate( 'td > span.date' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Author") );
		$Row->subordinate( 'td > input.form-control(text):author', array('placeholder' => t("Author")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Comment") );
		$Row->subordinate( 'td > textarea.form-control:comment', array('placeholder' => t("Comment"), 'rows' => 8) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Public") );
		$Row->subordinate( 'td > label' )
			->subordinate( 'input(checkbox):public' );//->append( t("confirmed") );

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.remove', t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.save', t("Save") );
	}


	/** Baut die Eingabemaske
	 * @param \rsCore\Container $Container
	 */
	public function buildCreationModal( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => 'add') );
		$Modal = $Form->subordinate( 'div#feedbackCreationModal.modal fade', array('aria-hidden' => 'true') )
						->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalHead = $Modal->subordinate( 'div.modal-header' );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFoot = $Modal->subordinate( 'div.modal-footer' );

		$ModalHead->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
				->subordinate( 'span', array('aria-hidden' => 'true'), '&times;' );
		$ModalHead->subordinate( 'h1.modal-title', t("Add feedback") );

		$ModalFoot->subordinate( 'button.btn.btn-primary.save', t("Save") );

		$Form = $ModalBody;

		$Form->subordinate( 'p > input.form-control(text):author', array('placeholder' => t("Author")) );
		$Form->subordinate( 'p > textarea.form-control:comment', array('placeholder' => t("Comment"), 'rows' => 8) );

/*
		$ClientSelector = $Form->subordinate( 'select.selectize:clientId', array('placeholder' => t("Select client")) );
		$ClientSelector->subordinate( 'option', t("Select client") );
		foreach( \Site\Client::getClients() as $Client ) {
			$ClientSelector->subordinate( 'option', array('value' => $Client->getPrimaryKeyValue()), $Client->lastname .', '. $Client->firstname );
		}
*/
	}


/* API Plugin */

	/** Fügt ein neues Feedback ein
	 * @return boolean
	 */
	public function api_addFeedback( $params ) {
		self::throwExceptionIfNotPrivileged( 'add' );
	#	$Client = \Site\Client::getClientById( postVar('clientId') );
		$Feedback = \Site\Feedback::addFeedback( postVar('comment'), postVar('author') );
		if( $Feedback ) {
			$Feedback->public = postVar('public');
			$Feedback->adopt();
			return $Feedback->getColumns();
		}
		return false;
	}


	/** Listet die Feedbacks auf
	 * @return array
	 */
	public function api_listFeedbacks( $params ) {
		$Feedbacks = isset( $params['public'] ) ? \Site\Feedback::getPublicFeedbacks() :  \Site\Feedback::getFeedbacks();
		return self::buildFeedbacksList( $Feedbacks );
	}


	/** Speichert Veranstaltungsdetails
	 * @return array
	 */
	public function api_saveFeedback( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$Feedback = \Site\Feedback::getFeedbackById( postVar('id') );
		if( !$Feedback )
			return false;

	#	$Client = \Site\Client::getClientById( postVar('clientId') );
	#	if( !$Client )
	#		return false;


		$fields = array('author', 'comment');
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Feedback->set( $field, $value );
			}
		}

	#	$Feedback->clientId = $Client->getPrimaryKeyValue();
		$Feedback->public = postVar('public') == 'on' ? 1 : 0;

		if( $Feedback->adopt() )
			return $Feedback->getColumns();
		return false;
	}


	/** Löscht ein Feedback
	 * @return boolean
	 * @todo Prüfen ob das Feedback auch im Zuständigkeitsbereich liegt und gelöscht werden darf
	 */
	public function api_deleteFeedback( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$Feedback = \Site\Feedback::getFeedbackById( postVar('id') );
		if( $Feedback )
			return $Feedback->remove();
	}


	/** Gibt die Badge-Zahl zurück
	 * @return integer
	 */
	public function api_badgeCount( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		return null;
	}


}