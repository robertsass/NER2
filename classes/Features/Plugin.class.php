<?php
namespace Features;


interface PluginInterface {

	static function featureRegistration( \rsCore\FrameworkInterface $Framework );
	
	static function getDatatype();
	function getValueForToken( $token, array $tokens, $currentTokensIndex );

}


abstract class Plugin extends \rsCore\Plugin {
	
	
	const DATATYPE_STRING = 'STRING';
	const DATATYPE_NUMERIC = 'NUMERIC';
	const DATATYPE_BOOL = '{Yes,No}';
	

	/** Wird vom Exporter aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function featureRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		return $Plugin;
	}


	/** Gibt das vorhergehende Token zurück, oder einen leeren String, falls es kein vorhergehendes gibt
	 * @return string
	 */
	public static function getPreviousToken( array $tokens, $currentTokensIndex ) {
		$previousIndex = $currentTokensIndex - 1;
		if( isset( $tokens[ $previousIndex ] ) )
			return $tokens[ $previousIndex ];
		return '';
	}


	/** Gibt das nachfolgende Token zurück, oder einen leeren String, falls es kein nachfolgendes gibt
	 * @return string
	 */
	public static function getNextToken( array $tokens, $currentTokensIndex ) {
		$nextIndex = $currentTokensIndex + 1;
		if( isset( $tokens[ $nextIndex ] ) )
			return $tokens[ $nextIndex ];
		return '';
	}

	
}