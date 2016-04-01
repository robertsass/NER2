<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace rsCore;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface PageHeadInterface extends ProtectivePageHeadInterface {

	function getTemplate();
	function getProtectiveInstance();

	function init();
	function build();

	function getPagetitle();
	function setPagetitle( $title );

	function getStylesheets();
	function linkStylesheet( $stylesheetPath, $media );
	function unlinkStylesheet( $stylesheetPath );

	function getScripts();
	function linkScript( $scriptPath );
	function unlinkScript( $scriptPath );

	function getMetas();
	function addMetaName( $name, $content );
	function addMetaHttpEquiv( $httpEquiv, $content );
	function removeMetaName( $name );
	function removeMetaHttpEquiv( $httpEquiv );

	function getOthers();
	function addOther( $snippetOrContainerInstance );

	function getLinks();
	function addLink( $rel, $href, $type, $language );
	function removeLink( $rel, $href );

}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Observable
 */
class PageHead extends Observable implements PageHeadInterface, Reflectable {


	private $_Template;
	private $_pagetitle;
	private $_stylesheets;
	private $_scripts;
	private $_metas;
	private $_links;
	private $_others;


	/** Konstruktor
	 *
	 * @access public
	 * @final
	 * @param \rsCore\BaseTemplate $Template
	 * @return void
	 */
	final public function __construct( \rsCore\BaseTemplate $Template ) {
		$this->_Template = $Template;
		$Document = $Template->getDocument();
		if( $Document )
			$this->setPagetitle( $Document->getName() );
		$this->_stylesheets = array();
		$this->_scripts = array();
		$this->_metas = array();
		$this->_links = array();
		$this->_others = array();
		$this->init();
	}


	/** Gibt das Template zurück
	 *
	 * @access public
	 * @final
	 * @return \rsCore\BaseTemplate
	 */
	final public function getTemplate() {
		return $this->_Template;
	}


	/** Gibt eine geschützte Instanz, d.h. eine die keine Möglichkeiten zum Entfernen umfasst, zurück
	 *
	 * @access public
	 * @final
	 * @return \rsCore\ProtectivePageHead
	 */
	final public function getProtectiveInstance() {
		return new ProtectivePageHead( $this );
	}


	/** Dient als erweiterbarer Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
	}


	/** Baut den HTML-Head zu einem Container-Objekt zusammen
	 *
	 * @access public
	 * @return Container
	 */
	public function build() {
		$Body = $this->getTemplate()->getPageBody();
		$Head = new Container( 'head' );
		$Head->subordinate( 'title', $this->getPagetitle() );

		foreach( $this->getStylesheets() as $media => $stylesheetPaths )
			foreach( $stylesheetPaths as $stylesheetPath )
				$Head->subordinate( 'link', array(
					'rel' => 'stylesheet',
					'type' => 'text/css',
					'media' => $media,
					'href' => Core::functions()->rewriteResourceUrl( $stylesheetPath )
				) );

		foreach( $this->getScripts() as $scriptPath )
			$Body->subordinate( 'script', array(
				'type' => 'text/javascript',
				'src' => Core::functions()->rewriteResourceUrl( $scriptPath )
			) );

		foreach( $this->getMetas() as $type => $metas )
			foreach( $metas as $identifier => $content )
				$Head->subordinate( 'meta', array(
					$type => $identifier,
					'content' => $content
				) );

		foreach( $this->getOthers() as $snippetOrContainerInstance )
			$Head->swallow( $snippetOrContainerInstance );

		foreach( $this->getLinks() as $rel => $links )
			foreach( $links as $href => $attributes )
				$Head->subordinate( 'link', array_merge( $attributes, array(
					'rel' => $rel,
					'href' => Core::functions()->rewriteResourceUrl( $href )
				) ) );

		return $Head;
	}


	/** Gibt den Titel zurück
	 *
	 * @access public
	 * @return string
	 */
	final public function getPagetitle() {
		return $this->_pagetitle;
	}


	/** Setzt den Titel
	 *
	 * @access public
	 * @return object Selbstreferenz
	 */
	public function setPagetitle( $title ) {
		$this->_pagetitle = $title;
		return $this;
	}


	/** Gibt die verlinkten Stylesheets zurück
	 *
	 * @access public
	 * @return array
	 */
	final public function getStylesheets() {
		return $this->_stylesheets;
	}


	/** Verlinkt ein Stylesheet
	 *
	 * @access public
	 * @param string $stylesheetPath Pfad zur CSS-Datei
	 * @return object Selbstreferenz
	 */
	public function linkStylesheet( $stylesheetPath, $media="all" ) {
		if( !array_key_exists( $media, $this->_stylesheets ) )
			$this->_stylesheets[ $media ] = array();
		if( !in_array( $stylesheetPath, $this->_stylesheets[ $media ] ) )
			$this->_stylesheets[ $media ][] = $stylesheetPath;
		return $this;
	}


	/** Entfernt ein Stylesheet
	 *
	 * @access public
	 * @param string $stylesheetPath Pfad zur CSS-Datei
	 * @return object Selbstreferenz
	 */
	public function unlinkStylesheet( $stylesheetPath ) {
		foreach( $this->_stylesheets as $media => $stylesheets )
			foreach( $stylesheets as $index => $stylesheet )
				if( $stylesheet == $stylesheetPath )
					unset( $this->_stylesheets[ $media ][ $index ] );
		return $this;
	}


	/** Gibt die verlinkten Scripts zurück
	 *
	 * @access public
	 * @return array
	 */
	final public function getScripts() {
		return $this->_scripts;
	}


	/** Verlinkt ein Script
	 *
	 * @access public
	 * @param string $scriptPath Pfad zur Javascript-Datei
	 * @return object Selbstreferenz
	 */
	public function linkScript( $scriptPath ) {
		if( !in_array( $scriptPath, $this->_scripts ) )
			$this->_scripts[] = $scriptPath;
		return $this;
	}


	/** Entfernt ein Script
	 *
	 * @access public
	 * @param string $scriptPath Pfad zur Javascript-Datei
	 * @return object Selbstreferenz
	 */
	public function unlinkScript( $scriptPath ) {
		foreach( $this->_scripts as $index => $script )
			if( $script == $scriptPath )
				unset( $this->_scripts[ $index ] );
		return $this;
	}


	/** Gibt die verlinkten Metas zurück
	 *
	 * @access public
	 * @return array
	 */
	final public function getMetas() {
		return $this->_metas;
	}


	/** Fügt ein Meta-Name hinzu
	 *
	 * @access public
	 * @param string $name
	 * @param string $content
	 * @return object Selbstreferenz
	 */
	public function addMetaName( $name, $content ) {
		$this->_metas['name'][ $name ] = $content;
		return $this;
	}


	/** Fügt ein Meta-HTTP-Equiv hinzu
	 *
	 * @access public
	 * @param string $httpEquiv
	 * @param string $content
	 * @return object Selbstreferenz
	 */
	public function addMetaHttpEquiv( $httpEquiv, $content ) {
		$this->_metas['http-equiv'][ $httpEquiv ] = $content;
		return $this;
	}


	/** Entfernt einen Meta-Name
	 *
	 * @access public
	 * @param string $name
	 * @return object Selbstreferenz
	 */
	public function removeMetaName( $name ) {
		unset( $this->_metas['name'][ $name ] );
		return $this;
	}


	/** Entfernt einen Meta-HTTP-Equiv
	 *
	 * @access public
	 * @param string $httpEquiv
	 * @return object Selbstreferenz
	 */
	public function removeMetaHttpEquiv( $httpEquiv ) {
		unset( $this->_metas['http-equiv'][ $httpEquiv ] );
		return $this;
	}


	/** Gibt sonstige eingefügte Head-Inhalte zurück
	 *
	 * @access public
	 * @return array
	 */
	final public function getOthers() {
		return $this->_others;
	}


	/** Fügt sonstigen Head-Inhalt ein
	 *
	 * @access public
	 * @param mixed $snippetOrContainerInstance
	 * @return object Selbstreferenz
	 */
	public function addOther( $snippetOrContainerInstance ) {
		$this->_others[] = $snippetOrContainerInstance;
		return $this;
	}


	/** Gibt die eingefügten Header-Links zurück
	 *
	 * @access public
	 * @return array
	 */
	final public function getLinks() {
		return $this->_links;
	}


	/** Fügt einen Header-Link hinzu
	 *
	 * @access public
	 * @param string $rel
	 * @param string $href
	 * @param string $type
	 * @param string $language
	 * @return object Selbstreferenz
	 */
	public function addLink( $rel, $href, $type=null, $language=null ) {
		$array = array();
		if( $type )
			$array['type'] = $type;
		if( $language )
			$array['hreflang'] = $language;
		$this->_links[ $rel ][ $href ] = $array;
		return $this;
	}

	/** Entfernt einen Header-Link
	 *
	 * @access public
	 * @param string $rel
	 * @param string $href
	 * @return object Selbstreferenz
	 * @todo Datenstruktur evtl nochmal überdenken?
	 */
	public function removeLink( $rel, $href ) {
		unset( $this->_links[ $rel ][ $href ] );
		return $this;
	}


}