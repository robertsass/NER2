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
interface ObservableInterface {

	function registerObserver( $Object );
	function unregisterObserver( $Object );
	function callObservers( $method, $params );
	function observers();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
abstract class Observable extends CoreClass implements ObservableInterface {


	private $_observers;


	final public function registerObserver( $Object ) {
		if( !$this->_observers )
			$this->_observers = new ObserverSet();
		$this->_observers->registerObserver( $Object );
	}


	final public function unregisterObserver( $Object ) {
		if( $this->_observers )
			$this->_observers->unregisterObserver( $Object );
	}


	final public function callObservers( $method, $params ) {
		if( $this->_observers )
			$this->_observers->callObservers( $method, $params );
	}


	final public function observers() {
		if( !$this->_observers )
			$this->_observers = new ObserverSet();
		return $this->_observers;
	}


}