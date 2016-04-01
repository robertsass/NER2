<?php
namespace Features;


class SentenceIndex extends Plugin {


	public static function getDatatype() {
		return self::DATATYPE_NUMERIC;
	}


	public function getValueForToken( $token, array $tokens, $currentTokensIndex, $Formatter ) {
		return $currentTokensIndex;
	}


}