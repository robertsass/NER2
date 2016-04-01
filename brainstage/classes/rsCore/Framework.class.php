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
interface FrameworkInterface {

	public function registerHook( CoreClass $Object, $event, $specificMethod );
	public function unregisterHook( CoreClass $Object, $event );

	public function catchExceptions( $boolean );

	public function hasHooks( $event );
	public function getHooks( $event );
	public function callHooks( $event, $params, $indexByIdentifier );
	
	public function getHookedObjects();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Framework implements FrameworkInterface {


	private $_hooks;
	private $_catchExceptions = true;


/* Private methods */

	/**
	 * @internal
	 */
	private function init() {
		if( !$this->_hooks )
			$this->_hooks = array();
	}


/* Magic methods */

	public function __construct() {
		$this->init();
	}


	public function __get( $key ) {
		return $this->getHooks( $key );
	}


	public function __set( $key, $value ) {
		if( is_object( $value ) ) {
			$this->registerHook( $value, $key );
		}
	}


	/** Ruft die Hooks zu einem Event auf
	 *
	 * @access public
	 * @param string $method
	 * @param array $params
	 * @return void
	 */
	public function __call( $event, $params ) {
		$this->callHooks( $event, $params );
	}


/* Public methods */

	/** Registriert ein Objekt als Hook für ein Event
	 *
	 * @access public
	 * @param CoreClass $Object
	 * @param string $event
	 * @return object Selbstreferenz
	 */
	public function registerHook( CoreClass $Object, $event, $specificMethod=null ) {
		$objectId = $Object->getObjectId();
		$this->_hooks[ $event ][ $objectId ] = new FrameworkHook( $Object, $event, $specificMethod );
		return $this;
	}


	/** Entfernt den Hook eines Objekts für ein Event
	 *
	 * @access public
	 * @param CoreClass $Object
	 * @param string $event
	 * @return object Selbstreferenz
	 */
	public function unregisterHook( CoreClass $Object, $event ) {
		$objectId = $Object->getObjectId();
		unset( $this->_hooks[ $event ][ $objectId ] );
		return $this;
	}


	/** Stellt ein, ob Exceptions beim Aufruf der Hooks gefangen werden sollen
	 *
	 * @access public
	 * @param boolean $boolean
	 * @return object Selbstreferenz
	 */
	public function catchExceptions( $boolean ) {
		$this->_catchExceptions = $boolean ? true : false;
		return $this;
	}


	/** Prüft, ob zu einem Event Hooks registriert wurden
	 *
	 * @access public
	 * @param string $event
	 * @return boolean
	 */
	public function hasHooks( $event ) {
		return array_key_exists( $event, $this->_hooks ) && !empty( $this->_hooks[ $event ] );
	}


	/** Gibt die registrierten Objekte für ein Event zurück
	 *
	 * @access public
	 * @param string $event
	 * @return array
	 */
	public function getHooks( $event=null ) {
		if( $event === null )
			return $this->_hooks;
		if( !array_key_exists( $event, $this->_hooks ) )
			return array();
		return $this->_hooks[ $event ];
	}


	/** Ruft auf den Objekten, die für ein Event registriert wurden, eine Methode auf
	 *
	 * @access public
	 * @param string $event
	 * @param array $params Zu übergebende Parameter
	 * @param boolean $indexByIdentifier Bei true wird das zurückgegebene Array durch einen Objekt-Identifier indiziert
	 * @return array Array von Rückgaben
	 * @todo Exception fangen, still und leise protokollieren, ggf. weiterwerfen
	 */
	public function callHooks( $event, $params=null, $indexByIdentifier=false ) {
		if( $params === null )
			$params = array();
		$returns = array();
		foreach( $this->getHooks( $event ) as $Hook ) {
			try {
				$return = $Hook->call( $params );
				if( $indexByIdentifier ) {
					if( $Hook->getObject() instanceof \rsCore\PluginInterface )
						$identifier = $Hook->getObject()->getIdentifier();
					else
						$identifier = $Hook->getObject()->getObjectId();
					$returns[ $identifier ] = $return;
				}
				else {
					$returns[] = $return;
				}
			}
			catch( \Exception $Exception ) {
				if( $this->_catchExceptions )
					\rsCore\ErrorHandler::catchException( $Exception );
				else
					throw $Exception;
			}
		}
		return $returns;
	}


	/** Gibt sämtliche registrierten Objekte zurück
	 *
	 * @access public
	 * @return array
	 */
	public function getHookedObjects() {
		$objects = array();
		foreach( $this->_hooks as $event => $hooks ) {
			foreach( $hooks as $objectId => $Hook ) {
				if( !array_key_exists( $objectId, $objects ) )
					$objects[ $objectId ] = $Hook->getObject();
			}
		}
		return $objects;
	}


}