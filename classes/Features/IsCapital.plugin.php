<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Features;


/** IsCapitalPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2016 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class IsCapital extends Plugin {


	/** Gibt den Pagination-Index zurück
	 * @return \Brainstage\Setting
	 */
	public static function getDatatype() {
		return self::DATATYPE_NUMERIC;
	}


	/** Gibt die letzte iterierte Movie ID zurück
	 * @return \Brainstage\Setting
	 */
	public function getValueForToken( $token, array $tokens, $currentTokensIndex ) {
		$p = substr( $token, 0, 1 );
		return strtoupper( $p ) == $p && strtolower( $p ) != $p;
	}


}