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
interface BaseInterface {

    function getLanguage();

    function getChildDocuments( $onlyPublicAccessible );
    function getChildByTemplate( $templateName, $onlyPublicAccessible );
    function getChildUrlByTemplate( $templateName );

    function buildHead( \rsCore\ProtectivePageHeadInterface $Head );
    function buildBody( \rsCore\Container $Container );
    function buildContent( \rsCore\Container $Container );

}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\HTMLTemplate
 */
class Base extends \rsCore\HTML5Template implements BaseInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->hook( 'extendContent' );
	}


	/** Gibt die momentan ausgewählte Sprache zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getLanguage() {
		$languageCode = \rsCore\Localization::getLanguage();
		return \Brainstage\Language::getLanguageByShortCode( $languageCode );
	}


	/** Gibt die Kind-Dokumente ersten Levels dieser Site zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getChildDocuments( $onlyPublicAccessible=true ) {
		if( !$onlyPublicAccessible )
			return $this->getDocument()->getChildren();

		$children = array();
		foreach( $this->getDocument()->getChildren() as $Child ) {
			if( $Child->accessibility != 'public' )
				continue;
			$children[] = $Child;
		}
		return $children;
	}


	/** Gibt das Kind-Dokument mit dem gegebenen Template zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getChildByTemplate( $templateName, $onlyPublicAccessible=false ) {
		foreach( $this->getChildDocuments( $onlyPublicAccessible ) as $Child ) {
			if( $Child->getTemplateName() == $templateName )
				return $Child;
		}
		return null;
	}


	/** Gibt die URL zu einem Kind-Dokument des gegebenen Templates zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getChildUrlByTemplate( $templateName, $onlyPublicAccessible=false ) {
		$Child = $this->getChildByTemplate( $templateName, $onlyPublicAccessible );
		if( $Child )
			return $Child->getComposedUrl();
		return null;
	}


	/** Konfiguriert den HTML-Head
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 * @todo Merge as many resources as possible to one file
	 */
	public function buildHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		parent::buildHead( $Head );
		$Head->addMetaName( 'language', \rsCore\Localization::getLanguage() );

		$Head->linkStylesheet( '/static/bootstrap/css/bootstrap.min.css' );
		$Head->linkStylesheet( '/static/bootstrap/css/bootstrap-theme.min.css' );
/*
		$Head->linkScript( '/static/js/jquery-2.1.1.min.js' );
		$Head->linkScript( '/static/js/modernizr-2.6.2.min.js' );
		$Head->linkScript( '/static/bootstrap/js/bootstrap.min.js' );
		$Head->linkScript( '/static/js/main.js' );
*/

		$this->callHooks( 'extendHead', array( $Head->getProtectiveInstance() ) );
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 */
	public function extendHead( \rsCore\ProtectivePageHeadInterface $Head ) {
	}


	/** Baut den HTML-Body
	 *
	 * @access public
	 * @param \rsCore\Container $Body
	 * @return void
	 */
	public function buildBody( \rsCore\Container $Body ) {
		$BodyContainer = $Body->subordinate( 'div#body' );

		$Main = $BodyContainer->subordinate( 'div#main' );
		$Content = $Main->subordinate( 'div#content > div.inner' );

		$this->buildContent( $Content );
	}


	/** Baut den Content zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Body
	 * @return \rsCore\Container
	 */
	public function buildContent( \rsCore\Container $Content ) {
		$this->callHooks( 'extendContent', $Content );
		return $Content;
	}


	/** Hook zum Manipulieren des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContent( \rsCore\Container $Container ) {
		$ContentArea = $Container->subordinate( 'div#content-area' );
		$this->buildPageContent( $ContentArea, true );
		$this->callHooks( 'extendContentArea', $ContentArea );
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


}
