<?php
namespace Features;


class RightDistanceSignalWords extends DistanceSignalWords {


	/** Gibt der Attribut-Wert für das jeweilige Token zurück
	 * @return mixed
	 */
	public function getValueForToken( $token, array $tokens, $currentTokensIndex ) {
		$distance = null;
		$index = count( $tokens )-1;
		while( $index > $currentTokensIndex ) {
			$token = $tokens[ $index ];
			$isSignalWord = \rsCore\StringUtils::containsOne( $token, self::$signalWords );
			if( $isSignalWord ) {
				$distance = abs( $index - $currentTokensIndex );
			}
			$index--;
		}
		return $distance;
	}


}