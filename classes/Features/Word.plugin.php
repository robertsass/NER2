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
class Word extends Plugin {


	/** Gibt den Pagination-Index zurück
	 * @return \Brainstage\Setting
	 */
	public static function getDatatype() {
		return self::DATATYPE_STRING;
	}


	/** Gibt die letzte iterierte Movie ID zurück
	 * @return \Brainstage\Setting
	 */
	public function getValueForToken( $token, array $tokens, $currentTokensIndex, $Formatter ) {
		$annotation = $Formatter->getCurrentAnnotations( $currentTokensIndex );
		if( $annotation )
			return $annotation['token'];
		return $token;
	}


}