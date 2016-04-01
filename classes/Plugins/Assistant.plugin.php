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
interface AssistantInterface {
}


/** ReviewsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Assistant extends \rsCore\Plugin implements AssistantInterface, \Brainstage\Plugins\Dashboard\PluginInterface {


	const TAGNAME = 'movietitle';


/* Framework Registration */

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
		$Framework->registerHook( $Plugin, 'list', 'api_listReviews' );
		$Framework->registerHook( $Plugin, 'get', 'api_getReview' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveReview' );
		$Framework->registerHook( $Plugin, 'badge_count', 'api_badgeCount' );
	}


	/** Wird vom Brainstage-Plugin Dashboard aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function dashboardRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'buildWidget' );
	}


/* General */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
		parent::init();
	}


	/** Baut die Rückgabe der List-API-Abfrage zusammen
	 */
	protected static function buildReviewsList( array $Reviews ) {
		$list = array();
		foreach( $Reviews as $Review ) {
			$array = $Review->getColumns();
			$list[] = $array;
		}
		return $list;
	}


/* Dashboard Widget */

	/** Gibt den Titel des Dashboard-Widgets zurück
	 * @return string
	 */
	public static function getDashboardWidgetTitle() {
		return self::t("Assistant");
	}


	/** Baut das Widget
	 * @param \rsCore\Container $Container
	 */
	public function buildWidget( \rsCore\Container $Container ) {
		$percentage = round( \Site\Review::totalCount() / \Site\CrawlerReview::totalCount() *100 );
		$Counter = $Container->subordinate( 'div.huge-counter.centered' )
			->subordinate( 'span.number', $percentage .'%' )
			->append( 'span.description', self::t("Examined") );
	}


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Assistant");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/assistant.css' );
		$Head->linkScript( '/static/js/TextHighlighter.min.js' );
		$Head->linkScript( '/static/js/assistant.js' );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('method' => 'post', 'action' => 'save') );
		$Panel = $Form->subordinate( 'div.panel.panel-default > div.panel-body' );

		$this->buildToolbar( $Panel );

		$Reader = $Panel->subordinate( 'div.reader' );
		$Header = $Reader->subordinate( 'div.page-header' );
		$Header->subordinate( 'p > input(text).form-control.input-lg:title', array('placeholder' => t("Movie series title")) );
		$Header->subordinate( 'p > input(text).form-control:subtitle', array('placeholder' => t("Movie episode title")) );
		$Header->subordinate( 'p > a.sourceUrl', array('target' => '_blank') );
		$Reader->subordinate( 'input(hidden):id' );
		$Reader->subordinate( 'div.plaintext' );

		$Form->subordinate( 'input(submit).btn.btn-primary', array('value' => t("Continue")) );
	}


	/** Baut die Toolbar
	 * @param \rsCore\Container $Container
	 */
	public function buildToolbar( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'div.toolbar.row' );
	#	$this->buildTabBar( $Toolbar->subordinate( 'div.col-md-5' ) );
		$Toolbar->subordinate( 'div.col-xs-6 > button(button).undo-highlighting.btn.btn-default', array(
			'data-toggle' => 'tooltip',
			'data-placement' => 'right',
			'title' => "Markierung wiederrufen"
		) )->subordinate( 'i.icon-undo' );

		$RightColumn = $Toolbar->subordinate( 'div.col-xs-6' );
		$ButtonGroup = $RightColumn->subordinate( 'div.btn-group', array('data-toggle' => 'buttons') );
		$ButtonGroup->subordinate( 'label.btn.btn-danger > input(radio):decision=discard' )->append( t("Discard") );
		$ButtonGroup->subordinate( 'label.btn.btn-success > input(radio):decision=accept' )->append( t("Accept") );

		$RightColumn->subordinate( 'input(submit).btn.btn-primary', array('value' => t("Continue")) );
	}


	/** Baut die Tabbar zusammen
	 * @param \rsCore\Container $Container
	 */
	public function buildTabBar( \rsCore\Container $Container ) {
		$tabAttr = array('role' => 'tab', 'data-toggle' => 'tab');
		$Bar = $Container->subordinate( 'ul.nav.nav-tabs' );

		$tabs = array();
		foreach( $tabs as $param => $title ) {
			$attr = array_merge( $tabAttr, array('data-api-parameters' => $param) );
			$Bar->subordinate( 'li > a', $attr, $title );
		}
	}


/* API Plugin */

	/** Listet die Reviews auf
	 * @return array
	 */
	public function api_listReviews( $params ) {
		$Reviews = \Site\CrawlerReview::getAll();
		return self::buildReviewsList( $Reviews );
	}


	/** Gibt ein unbearbeitetes Review zurück
	 * @return array
	 */
	public function api_getReview( $params ) {
		$CrawlerReview = \Site\CrawlerReview::getUnexamined();
		$array = $CrawlerReview->getColumns();
		$array['movie'] = $CrawlerReview->getMovie()->getColumns();
		$array['sourceUrl'] = $CrawlerReview->getReviewSearch()->url;
		$array['brokenPlainText'] = nl2br( $CrawlerReview->plainText );
		return $array;
	}


	/** Speichert das Review oder verwirft es
	 * @return array
	 */
	public function api_saveReview( $params ) {
		$text = postVar('text');
		$markup = preg_replace( '/<span class=\"highlighted\".*?>(.*?)<\/span>/', '<'. self::TAGNAME .'>$1</'. self::TAGNAME .'>', $text );
		$markup = preg_replace( '/<(?!\/'. self::TAGNAME .'|'. self::TAGNAME .').*?>/', '', $markup );

		$CrawlerReview = \Site\CrawlerReview::getById( postVar('id') );
		if( $CrawlerReview ) {
			$Review = \Site\Review::add( $CrawlerReview->getMovie(), $CrawlerReview );
			if( $Review ) {
				$CrawlerReview->reviewId = $Review->getPrimaryKeyValue();
				$Review->title = postVar('title');
				$Review->subtitle = postVar('subtitle');
				$Review->text = $markup;
				$Review->examined = 1;
				if( postVar('decision') == 'discard' )
					$Review->suitable = 0;
				if( postVar('decision') == 'accept' )
					$Review->suitable = 1;
				$CrawlerReview->adopt();
				if( $Review->adopt() ) {
					return $Review->getColumns();
				}
			}
		}
		return false;
	}


	/** Gibt die Badge-Zahl zurück
	 * @return integer
	 */
	public function api_badgeCount( $params ) {
		return \Site\CrawlerReview::totalCount();
	}


}