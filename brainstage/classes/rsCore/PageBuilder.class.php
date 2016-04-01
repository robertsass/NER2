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
interface PageBuilderInterface {

	function getFramework();
	function getRequestHandler();
	function getDocument();
	function getTemplate();
	function registerHook( $Object, $event );
	function unregisterHook( $Object, $event );
	function callHooks( $event, $params );

	function build();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class PageBuilder implements PageBuilderInterface {


	private $_Framework;
	private $_RequestHandler;
	private $_Document;
	private $_Template;


	final public function __construct() {
		$this->_Framework = new Framework();
		if( getVar( 'd', false ) ) {
			$this->_Document = \Brainstage\Document::getDocumentById( intval( getVar('d') ), null );
			$this->_Document->setLanguage( Localization::getLanguage() );
			$this->_Template = $this->instantiateTemplate();
		} else {
			$RequestPath = Core::core()->getRequestPath();
			$this->_RequestHandler = $RequestPath->getRequestHandler();
			if( $this->_RequestHandler->getTargetType() == RequestHandler::TARGETTYPE_DOCUMENT ) {
				$this->_Document = $this->_RequestHandler->getTarget();
				if( !is_object( $this->_Document ) ) {
					if( empty( $RequestPath->path ) )
						Core::functions()->redirect( './brainstage' );	// @todo statt zu Brainstage auf eine 404-Seite umleiten
					Core::functions()->redirect( '../' );
				}
				$this->_Document->setLanguage( Localization::getLanguage() );
				$this->_Template = $this->instantiateTemplate();
			}
		}
		$this->init();
	}


	/** Instantiiert das dem Dokument zugewiesene Template
	 *
	 * @access protected
	 * @return object
	 */
	protected function instantiateTemplate() {
		$baseTemplate = '\\'. \Autoload::TEMPLATE_NAMESPACE .'\\Base';
		$templateName = '\\'. \Autoload::TEMPLATE_NAMESPACE .'\\'. $this->getDocument()->getTemplateName();
		if( $templateName != null && class_exists( $templateName ) )
			return new $templateName( $this, $this->getRequestHandler(), $this->getDocument() );
		elseif( class_exists( $baseTemplate ) )
			return new $baseTemplate( $this, $this->getRequestHandler(), $this->getDocument() );
		else
			throw new Exception( "Template '". $templateName ."' could not be found." );
		return null;
	}


	/** Gibt das Framework-Objekt zurück
	 *
	 * @access public
	 * @return Framework
	 */
	public function getFramework() {
		return $this->_Framework;
	}


	/** Gibt den RequestHandler zurück
	 *
	 * @access public
	 * @return RequestHandler
	 */
	public function getRequestHandler() {
		return $this->_RequestHandler;
	}


	/** Gibt das Dokument zurück
	 *
	 * @access public
	 * @return Document
	 */
	public function getDocument() {
		return $this->_Document;
	}


	/** Gibt das Template zurück
	 *
	 * @access public
	 * @return object
	 */
	public function getTemplate() {
		return $this->_Template;
	}


	/** Registriert einen Hook
	 *
	 * @param object $Object
	 * @param string $event
	 * @param string|null $method
	 * @return object Selbstreferenz
	 */
	public function registerHook( $Object, $event, $method=null ) {
		$this->getFramework()->registerHook( $Object, $event, $method );
		return $this;
	}


	/** Entfernt einen Hook
	 *
	 * @param object $Object
	 * @param string $event
	 * @return object Selbstreferenz
	 */
	public function unregisterHook( $Object, $event ) {
		$this->getFramework()->unregisterHook( $Object, $event );
		return $this;
	}


	/** Ruft Hooks eines Events auf
	 *
	 * @param string $event
	 * @param array $params
	 * @return array
	 */
	public function callHooks( $event, $params ) {
		return $this->getFramework()->callHooks( $event, $params );
	}


	/** Dient als Konstruktor-Erweiterung
	 *
	 * @access protected
	 */
	protected function init() {
	}


	/** Startet den Zusammenbau der Seite und gibt den Quelltext aus
	 *
	 * @access public
	 */
	public function build( $output=true ) {
		$source = $this->getTemplate()->build( $output );
		if( $output )
			echo $source;
		else
			return $source;
	}


}