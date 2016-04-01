<?php
namespace Features;


class TreeAnnotation extends Plugin {
	
	
	/** Liste von Signalwörtern
	 */
	public static $annotations = array(
		"NC",
		"VC",
		"PC"
	);


	/** Gibt den Datentyp für dieses Attribut zurück
	 * @return string
	 */
	public static function getDatatype() {
		$annotations = array('_');
		foreach( self::$annotations as $annotation ) {
			$annotations[] = 'B-'. $annotation;
			$annotations[] = 'I-'. $annotation;
		}
		return '{'. implode( ',', $annotations ) .'}';
	}


	/** Gibt der Attribut-Wert für das jeweilige Token zurück
	 * @return mixed
	 */
	public function getValueForToken( $token, array $tokens, $currentTokensIndex, $Formatter ) {
		$annotation = $Formatter->getCurrentAnnotations( $currentTokensIndex );
		if( $annotation )
			$mark = $annotation['tree_annotation'];
		return $mark ? $mark : '_';
	}


}