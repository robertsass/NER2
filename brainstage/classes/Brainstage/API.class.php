<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface APIInterface {
}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class API extends \rsCore\CustomAPI implements APIInterface {


	/** Konstruktor-Erweiterung
	 */
	public function init() {
		parent::init();

		if( getVar('_verbose') && user()->isSuperAdmin() )
			$this->verbose();
	}


	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access public
	 * @return void
	 */
	public function initPlugins() {
		foreach( $this->getPlugins() as $pluginName ) {
			if( isLoggedin() && user()->mayUseClass( $pluginName ) ) {
				$initialized = $this->initPlugin( $pluginName );
			}
		}
	}


	/** Lädt sämtliche Plugins
	 *
	 * @access public
	 * @return array
	 */
	public function getPlugins() {
		return Brainstage::getPlugins();
	}


	/** Initialisiert ein Plugin
	 *
	 * @access public
	 * @return void
	 */
	public function initPlugin( $pluginName ) {
		$APIFramework = new \rsCore\APIFramework( $this );
		try {
			if( is_callable( $pluginName .'::apiRegistration' ) )
				$pluginName::apiRegistration( $APIFramework );
			if( is_callable( $pluginName .'::registerPrivileges' ) )
				Brainstage::registerPrivileges( $pluginName, $pluginName::registerPrivileges() );
			return true;
		} catch( \Exception $Exception ) {
			\rsCore\ErrorHandler::catchException( $Exception );
		}
		return false;
	}


	/** Gibt einen Index aller registrierten Methoden aus
	 * @param array $param
	 * @return array
	 */
	protected function indexAction( $param ) {
		$array = array();

		if( $param['output'] == 'list' ) {
			foreach( $this->getFrameworks() as $identifier => $Framework ) {
				foreach( array_keys( $Framework->getHooks() ) as $method )
					$array[] = $identifier .'/'. $method;
			}
		}

		else {
			foreach( $this->getFrameworks() as $identifier => $Framework ) {
				$array[ $identifier ] = array_keys( $Framework->getHooks() );
			}
		}

		return $array;
	}


}