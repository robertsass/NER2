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
interface BaseTemplateInterface {

	function __construct( $InstantiatingParent, RequestHandler $Request, \Brainstage\Document $Document );
	function getConstructor();
	function getDocument();
	function getRequest();
	function hook( $event, $method );
	function unhook( $event );
	function callHooks( $event, $params );

	function init();
	function build();

}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Observable
 */
abstract class BaseTemplate extends Observable implements BaseTemplateInterface, Reflectable {


	private $_Constructor;
	private $_Document;
	private $_Request;


	/** Erstellt die Verknüpfung zum Framework und registriert die Observer gemäß dem Observer-Pattern,
	 * um gegenseitig Events auszulösen.
	 *
	 * @access public
	 * @final
	 * @param object $InstantiatingParent
	 * @param RequestHandler $Request
	 * @param \Brainstage\Document $Document
	 * @return void
	 */
	final public function __construct( $InstantiatingParent, RequestHandler $Request=null, \Brainstage\Document $Document=null ) {
		if( is_object( $InstantiatingParent ) )
			$this->_Constructor = $InstantiatingParent;
		else
			throw new Exception( "Templates want to know the instantiating object as the first parameter." );
		$this->_Document = $Document;
		$this->_Request = $Request;
#		if( $InstantiatingParent instanceof Observable )
#			$InstantiatingParent->registerObserver( $this );
#		$this->registerObserver( $InstantiatingParent );
		$this->init();
	}


	/** Gibt das Objekt zurück, welches das Template-Objekt instanziiert hat
	 *
	 * @access public
	 * @final
	 * @return void
	 */
	final public function getConstructor() {
		return $this->_Constructor;
	}


	/** Gibt die Document-Instanz zurück
	 *
	 * @access public
	 * @final
	 * @return Document
	 */
	final public function getDocument() {
		return $this->_Document;
	}


	/** Gibt den RequestHandler zurück
	 *
	 * @access public
	 * @final
	 * @return RequestHandler
	 */
	final public function getRequest() {
		return $this->_Request;
	}


	/** Registriert einen Hook im PageBuilder
	 *
	 * @access public
	 * @final
	 * @return mixed Selbstreferenz oder null
	 */
	final public function hook( $event, $method=null ) {
		if( method_exists( $this->getConstructor(), 'registerHook' ) ) {
			$this->getConstructor()->registerHook( $this, $event, $method );
			return $this;
		}
		else
			throw new Exception( "Instantiating Template seems not to support registering hooks." );
		return null;
	}


	/** Entfernt einen Hook im PageBuilder
	 *
	 * @access public
	 * @final
	 * @return mixed Selbstreferenz oder null
	 */
	final public function unhook( $event ) {
		if( method_exists( $this->getConstructor(), 'unregisterHook' ) ) {
			$this->getConstructor()->unregisterHook( $this, $event );
			return $this;
		}
		else
			throw new Exception( "Instantiating Template seems not to support unregistering hooks." );
		return null;
	}


	/** Ruft im PageBuilder die Hooks eines Events auf
	 *
	 * @access public
	 * @final
	 * @return void
	 */
	final public function callHooks( $event, $params=null ) {
		if( method_exists( $this->getConstructor(), 'callHooks' ) ) {
			$this->getConstructor()->callHooks( $event, $params );
		}
		else
			throw new Exception( "Instantiating Template seems not to support hooks." );
	}


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		// Wenn angefordert, Datei-Anfrage behandeln
		\rsCore\FileManager::instance()->handleDownload();
	}


	/** Gibt den Quelltext aus
	 *
	 * @access public
	 * @return string
	 */
	public function build() {
	}


}