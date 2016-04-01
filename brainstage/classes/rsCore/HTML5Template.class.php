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
interface HTML5TemplateInterface {
}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends BaseTemplate
 */
class HTML5Template extends HTMLTemplate implements HTML5TemplateInterface {


	/** Gibt den Doctype zurück
	 *
	 * @access public
	 * @return string
	 */
	final public function getDoctype() {
		return '<!DOCTYPE html>';
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @return void
	 */
	public function buildHead( ProtectivePageHeadInterface $Head ) {
		parent::buildHead( $Head );
		$Head->addOther( new Container('meta', array('charset' => 'UTF-8') ) );
	}


}