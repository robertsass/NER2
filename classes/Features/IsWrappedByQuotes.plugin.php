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
class IsWrappedByQuotes extends Plugin {


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
		$quotationLevel = 0;
		foreach( $tokens as $index => $token ) {
			if( $currentTokensIndex == $index ) {
				if( $quotationLevel > 0 )
					return true;
				else
					return false;
			}
			if( $token == '"' || $token == "»" || $token == "«" || $token == "„" || $token == "“" ) {
				if( $quotationLevel % 2 == 0 )
					$quotationLevel++;
				else
					$quotationLevel--;
			}
		}
		return false;
	}


}