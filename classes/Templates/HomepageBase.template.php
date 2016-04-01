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
interface HomepageBaseInterface {

	function extendHead( \rsCore\ProtectivePageHeadInterface $Head );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class HomepageBase extends Base implements HomepageBaseInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->initSidebar();

		$this->hook( 'extendHead' );
		$this->hook( 'extendTop', 'buildTopBanner' );
		$this->hook( 'extendMenu', 'buildCityMenu' );
		$this->hook( 'extendContent' );
	}


	/** Initialisiert die Sidebar-Hooks
	 *
	 * @access public
	 * @return void
	 */
	public function initSidebar() {
		parent::init();
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 */
	public function extendHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		$Head->linkStylesheet( '/static/css/Homepage.css' );
		$Head->linkScript( '/static/js/Homepage.js' );
	}


	/** Hook zum Manipulieren des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContent( \rsCore\Container $Container ) {
		$ContentArea = $Container->subordinate( 'div#content-area' );
		$this->buildPageContent( $ContentArea, false );
		$this->callHooks( 'extendContentArea', $ContentArea );
	}


	/** Baut den Footer zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildFooter( \rsCore\Container $Container ) {
		$Footer = parent::buildFooter( $Container );
		$Footer->subordinate( 'h3', "Hello :)" );
		return $Footer;
	}


	/** Baut den Standard Content
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildPageContent( \rsCore\Container $Container, $printTitle=true ) {
		if( $printTitle )
			$Container->subordinate( 'h1', $this->getDocument()->getName() );
		$Container->swallow( $this->getDocument()->getContent() );
		return $Container;
	}


	/** Baut den Banner
	 *
	 * @access public
	 * @param \rsCore\Container $Top
	 * @return void
	 */
	public function buildTopBanner( \rsCore\Container $Top ) {
		$Banner = $Top->parent()->parent()->parent()->subordinate( 'div#banner' );
		$Banner->subordinate( 'img.parallax', array('src' => '/media/de/bonn/banner.jpg') );
		$BannerContent = $Banner->subordinate( 'div.inner > div.content' );

		$nextEvents = $this->getUpcomingEvents(1);
		$NextEvent = is_array($nextEvents) ? current($nextEvents) : null;
		$LatestQuote = $this->getLatestQuote();
		$RandomQuote = $this->getRandomQuote();
		$Quote = $RandomQuote;

		if( $NextEvent ) {
			$BannerContent->subordinate( 'h2', t("Next Nightfever") .': '. $NextEvent->start->format( t('Y-m-d', 'Date with full year') ) );
		}

		if( $Quote ) {
			$BannerContent->subordinate( 'p', '&laquo;'. $Quote->text .'&raquo;' );
			$BannerContent->subordinate( 'p', $Quote->author .' ('. $Quote->age .')' );
		}
	}


	/** Füllt das Städte-spezifische Menü
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return \rsCore\Container
	 */
	public function buildCityMenu( \rsCore\Container $Menu ) {
		$Menu->subordinate( 'div.title', $this->getCity()->name );
		$MenuList = $Menu->subordinate( 'ul#city-menu' );
		if( $this->getCityNode() ) {
			foreach( $this->getCityNode()->getChildren() as $i => $Child ) {
				$isSelected = $Child->getLeftValue() <= $this->getDocument()->getLeftValue() && $Child->getRightValue() >= $this->getDocument()->getRightValue();
				$Link = $MenuList->subordinate( 'li'. ($isSelected ? '.selected' : '') .' > a', $Child->getName() );
				$Link->addAttribute( 'href', $Child->getComposedUrl() );
			}
		}
		return $MenuList;
	}


}