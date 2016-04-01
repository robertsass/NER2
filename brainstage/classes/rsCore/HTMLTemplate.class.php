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
interface HTMLTemplateInterface {

	function getPageHead();
	function getPageBody();

	function getDoctype();
	function buildHead( ProtectivePageHeadInterface $Head );
	function buildBody( Container $Body );

	function build();

}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends BaseTemplate
 */
class HTMLTemplate extends BaseTemplate implements HTMLTemplateInterface {


	private $_PageHead;
	private $_PageBody;


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->_PageHead = new PageHead( $this );
		$this->_PageBody = new Container( 'body' );
	}


	/** Gibt die PageHead-Instanz zurück
	 *
	 * @access public
	 * @return PageHead
	 */
	public function getPageHead() {
		return $this->_PageHead;
	}


	/** Gibt den HTML-Body-Container zurück
	 *
	 * @access public
	 * @return Container
	 */
	public function getPageBody() {
		return $this->_PageBody;
	}


	/** Gibt den Doctype zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getDoctype() {
		return '';
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @return void
	 */
	public function buildHead( ProtectivePageHeadInterface $Head ) {
		$Head->addMetaName( 'generator', "Brainstage 2" );
	}


	/** Hook zum Manipulieren des HTML-Bodys
	 *
	 * @access public
	 * @return void
	 */
	public function buildBody( Container $Body ) {
		$Body->swallow( $this->getDocument()->getContent() );
#		print_r( Localization::getUseragentLanguages() );
	}


	/** Hook zum Manipulieren des HTML-Bodys
	 *
	 * @access public
	 * @return string
	 */
	public function build() {
		$this->buildHead( $this->getPageHead() );
		$this->buildBody( $this->getPageBody() );

		// Calling hooks
		$ProtectedPageHead = $this->getPageHead()->getProtectiveInstance();
		$this->callHooks( 'buildHead', array( $ProtectedPageHead ) );

		// Build HTML
		$Page = new \rsCore\Container( 'html' );
		$Page->swallow( $this->getPageHead()->build() );
		$Page->swallow( $this->getPageBody() );
		$source = $this->getDoctype() ."\n";
		$source .= $Page->summarize();

		// Send Header
		header( 'Content-Type: text/html; charset=utf-8' );

		// Return HTML
		return ltrim( $source );
	}


/* Protected methods */

	/** Gibt den Seitentitel zurück
	 *
	 * @access public
	 * @return string
	 */
	protected function getPagetitle() {
		return $this->_Document->getName();
	}


}