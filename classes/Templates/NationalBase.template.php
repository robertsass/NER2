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
interface NationalBaseInterface {

	function extendHead( \rsCore\ProtectivePageHeadInterface $Head );
	function extendTop( \rsCore\Container $Container );
	function extendContent( \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class NationalBase extends Base implements NationalBaseInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendHead' );
		$this->hook( 'extendTop' );
		$this->hook( 'extendMenu', 'extendMenu' );
		$this->hook( 'extendContent' );
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 */
	public function extendHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		$Head->linkStylesheet( '/static/css/national.css' );
		$Head->linkScript( '/static/js/national.js' );
	}


	/** Hook zum Erweitern der Topbar
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendTop( \rsCore\Container $Container ) {
		$this->buildNewsticker( $Container );
	}


	/** Hook zum Manipulieren des Menüs
	 *
	 * @access public
	 * @param \rsCore\Container $Menu
	 * @return void
	 */
	public function extendMenu( \rsCore\Container $Menu ) {
		$Menu->subordinate( 'div.title', $this->getCountry()->name );
		$MenuList = $Menu->subordinate( 'ul#city-list' );

		$cities = array();
		$Country = $this->getCountry();
		if( $Country ) {
			foreach( $Country->getCities() as $City )
				$cities[ $City->name ] = $City;
			ksort( $cities );

			$cityNodeId = $this->getCity() ? $this->getCity()->id : null;
			foreach( $cities as $City ) {
				$Country = $City->getCountry();
#				$url = '//'. $Country->shortname .'.'. rsCore()->getRequestPath()->domain->domainbase .'/'. urlencode( $City->name );
				$url = '//'. $City->shortname .'.'. rsCore()->getRequestPath()->domain->domainbase;
				$attr = array('href' => $url);
				$Link = $MenuList->subordinate( 'li'. ($isSelected ? '.selected' : '') .' > a', $attr, $City->name );
			}
		}
		return $MenuList;


		if( $this->getCountryNode() ) {
			foreach( $this->getCountry()->getCities() as $City ) {
				$Document = $City->getDocument();
				$isSelected = $Document->getLeftValue() <= $this->getDocument()->getLeftValue() && $Document->getRightValue() >= $this->getDocument()->getRightValue();
				$Link = $MenuList->subordinate( 'li'. ($isSelected ? '.selected' : '') .' > a', $Document->getName() );
				$Link->addAttribute( 'href', $Document->getComposedUrl() );
			}
		}
		return $MenuList;
	}


	/** Hook zum Erweitern des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContent( \rsCore\Container $Container ) {
		$ContentArea = $Container->subordinate( 'div#content-area' );
		$ContentArea->swallow( $this->getDocument()->getContent() );
		$this->callHooks( 'extendContentArea', $ContentArea );
	}


	/** Baut den Newsticker
	 *
	 * @access public
	 * @param \rsCore\Container $Top
	 * @return void
	 */
	public function buildNewsticker( \rsCore\Container $Top ) {
		$Banner = $Top->subordinate( 'div#newsticker > ul' );
		$Banner->subordinate( 'li', "Alle Meldungen" );
		$Banner->subordinate( 'li' )
			->subordinate( 'span.datestamp', "05.03." )
			->parent()->swallow( "Nightfever México hat ein Video hochgeladen" );
		$Banner->subordinate( 'li' )
			->subordinate( 'span.datestamp', "03.02." )
			->parent()->swallow( "Nightfever Hamburg hat eine neue Fotogalerie angelegt" );
		$Banner->subordinate( 'li' )
			->subordinate( 'span.datestamp', "01.01." )
			->parent()->swallow( "Nightfever Paderborn bekam ein neues Feedback" );
/*
		$Banner->subordinate( 'li' )
			->subordinate( 'span.datestamp', "11:59" )
			->parent()->swallow( "Heute finden gleich 7 Nightfever statt" );
*/
	}


}