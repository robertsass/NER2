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
 */
class CustomAPI extends API {


	/** Gibt die Parameter 1:1 wieder aus
	 * @param array $param
	 * @return array
	 */
	protected function helloworldAction( $param ) {
		return $param;
	}


}