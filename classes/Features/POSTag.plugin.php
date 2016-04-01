<?php
namespace Features;


class POSTag extends Plugin {


	/** Gibt den Datentyp für dieses Attribut zurück
	 * @return string
	 */
	public static function getDatatype( $Formatter ) {
		$posTags = array();
		foreach( $Formatter->getPOSTags() as $value ) {
			if( \rsCore\StringUtils::containsOne( $value, array(',', "'") ) )
				$value = '"'. $value .'"';
			elseif( \rsCore\StringUtils::containsOne( $value, array(',', '"') ) )
				$value = "'". $value ."'";
			$posTags[] = $value;
		}

		return '{'. implode( ',', $posTags ) .'}';
	}


	/** Gibt der Attribut-Wert für das jeweilige Token zurück
	 * @return mixed
	 */
	public function getValueForToken( $token, array $tokens, $currentTokensIndex, $Formatter ) {
		$annotation = $Formatter->getCurrentAnnotations( $currentTokensIndex );
		if( $annotation )
			$posTag = $annotation['pos_tag'];
		return $posTag ? $posTag : '?';
	}


}