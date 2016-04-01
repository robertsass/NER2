<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Templates;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface AuthInterface {

	function init();
	function build();
	function setErrors( array $errors );

	function buildHead( \rsCore\ProtectivePageHeadInterface $Head );
	function buildBody( \rsCore\Container $Body );
	function buildMainContent( \rsCore\Container $Container );

}


/** AuthTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\HTMLTemplate
 */
class Auth extends \rsCore\HTMLTemplate implements AuthInterface {

	private $_errors;


/* Private methods */

	protected function getHooks( $event ) {
		return $this->getConstructor()->getFramework()->getHooks( $event );
	}


	protected function getHookIdentifier( $Hook, $fullIdentifier=null, $lowercase=true ) {
		$HookedObject = $Hook->getObject();
		if( $fullIdentifier === null ) {
			$isBrainstagePlugin = strpos( $HookedObject->getReflection()->getNamespaceName(), 'Brainstage' ) !== false;
			$fullIdentifier = !$isBrainstagePlugin;
		}
		$identifier = $HookedObject->getIdentifier( $fullIdentifier );
		return $lowercase ? strtolower( $identifier ) : $identifier;
	}


/* Public methods */

	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->unhook( 'buildHead' );
		$this->unhook( 'buildBody' );

		$this->hook( 'buildNavigator' );
		$this->hook( 'buildMainContent' );
	}


	/** Hook zum Manipulieren des HTML-Bodys
	 *
	 * @access public
	 * @return string
	 */
	public function build() {
		$this->buildHead( $this->getPageHead() );
		$this->buildBody( $this->getPageBody() );

		// Build HTML
		$Page = new \rsCore\Container( 'html' );
		$Page->swallow( $this->getPageHead()->build() );
		$Page->swallow( $this->getPageBody() );

		// Send Header
		header( 'Content-Type: text/html; charset=utf-8' );

		// Return HTML
		return $Page->summarize();
	}


	/** Übergibt dem Brainstage-Template eventuell aufgetretene Fehler
	 *
	 * @param array $errors
	 * @access public
	 * @return void
	 */
	public function setErrors( array $errors ) {
		if( is_array( $this->_errors ) )
			$errors = array_merge( $this->_errors, $errors );
		$this->_errors = $errors;
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @return void
	 * @todo Merge as many resources as possible to one file
	 */
	public function buildHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		parent::buildHead( $Head );
		$Head->setPageTitle( "Brainstage - ". \Brainstage\Brainstage::translate("Login") );
		$Head->addMetaName( 'language', \rsCore\Localization::getLanguage() );
		$Head->linkStylesheet( 'static/bootstrap/css/bootstrap.min.css' );
		$Head->linkStylesheet( 'static/bootstrap/css/bootstrap-theme.min.css' );
		$Head->linkStylesheet( 'static/css/grid.css' );
		$Head->linkStylesheet( 'static/css/main.css' );
		$Head->linkScript( 'static/js/jquery.js' );
		$Head->linkScript( 'static/js/jquery-ui.min.js' );
		$Head->linkScript( 'static/bootstrap/js/bootstrap.min.js' );
		$Head->linkScript( 'static/js/extensions.js' );

		$this->callHooks( 'buildHead', array( $this->getPageHead()->getProtectiveInstance() ) );
	}


	/** Hook zum Manipulieren des HTML-Bodys
	 *
	 * @access public
	 * @return void
	 */
	final public function buildBody( \rsCore\Container $Body ) {
		$Body->addAttribute( 'id', 'login-screen' );
		$this->buildMainContent( $Body );
	}


	/** Baut den Main-Container auf
	 *
	 * @access public
	 * @return void
	 */
	final public function buildMainContent( \rsCore\Container $Container ) {
		$this->buildLoginForm( $Container );
		$this->buildFooter( $Container );
	}


	/** Baut das Login-Formular
	 *
	 * @access public
	 * @return void
	 */
	public function buildLoginForm( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form#login-form', array('method' => 'post', 'action' => './') );
		$Form->subordinate( 'h1', "Brainstage" );
		$Box = $Form->subordinate( 'div.form-group#login-box' );

		$this->buildErrorList( $Box );

		$Box->subordinate( 'p > input.form-control(text)', array('name' => 'username', 'placeholder' => \Brainstage\Brainstage::translate("Username")) );
		$Box->subordinate( 'p > input.form-control(password)', array('name' => 'password', 'placeholder' => \Brainstage\Brainstage::translate("Password")) );
		$Box->subordinate( 'p > input.btn btn-primary(submit)', array('value' => \Brainstage\Brainstage::translate("Login")) );
	}


	/** Baut die eventuelle Fehlermeldung
	 *
	 * @param \rsCore\Container $Container
	 * @access public
	 * @return void
	 */
	public function buildErrorList( \rsCore\Container $Container ) {
		if( is_array( $this->_errors ) && !empty( $this->_errors ) ) {
			$Container = $Container->subordinate( 'div.alert.alert-danger' );
			foreach( $this->_errors as $error ) {
				if( is_object( $error ) && $error instanceof \Exception )
					$error = $error->getMessage();
				$Container->subordinate( 'p', $error );
			}
		}
	}


	/** Baut den Footer
	 *
	 * @access public
	 * @return void
	 */
	public function buildFooter( \rsCore\Container $Container ) {
		$Footer = $Container->subordinate( 'div#footer' );
		$Footer->subordinate( 'p', "made with love by " )
			->subordinate( 'a', array('href' => 'http://www.brainedia.com'), "Brainedia" )
			->parent()
			->swallow( " &nbsp;&nbsp;&nbsp;&nbsp; &copy; 2010-". date('Y') );
	}


}