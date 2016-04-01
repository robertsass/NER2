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
interface SiteBaseInterface {

	function extendHead( \rsCore\ProtectivePageHeadInterface $Head );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class SiteBase extends Base implements SiteBaseInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendHead' );
		$this->hook( 'extendTop', 'buildTopBanner' );
		$this->hook( 'extendMenu', 'buildCityMenu' );
		$this->hook( 'extendContent' );
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 */
	public function extendHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		$Head->linkStylesheet( '/static/css/regional.css' );
		$Head->linkScript( '/static/js/regional.js' );
	}


	/** Hook zum Manipulieren des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContent( \rsCore\Container $Container ) {
		$Container = $Container->subordinate( 'div.row' );
		$Content = $Container->subordinate( 'div.eight columns' );
		$Sidebar = $Container->subordinate( 'div#sidebar.four columns' );

	#	$Content->subordinate( 'h1', $this->getDocument()->getName() );
		$Content->swallow( $this->getDocument()->getContent() );

		$More = $Container->subordinate( 'section#more' );
		$More->subordinate( 'h2', "Weitere Themen" );
		$Kacheln = $More->subordinate( 'div.row' );
		$Kacheln->subordinate( 'div.four columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/more1.png') )->parent()
			->swallow( 'Internationales Jugendtreffen in Assisi' );
		$Kacheln->subordinate( 'div.four columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/more2.jpg') )->parent()
			->swallow( 'Nightfever beim Katholikentag 2014' );
		$Kacheln->subordinate( 'div.four columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/more3.png') )->parent()
			->swallow( 'Weltjugendtag in Krakau' );
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

		$BannerContent->subordinate( 'h2', $this->getDocument()->getName() );
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