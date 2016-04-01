<?php
namespace Features;


class IsMovieTitle extends Plugin {


	/** Gibt den Datentyp für dieses Attribut zurück
	 * @return string
	 */
	public static function getDatatype() {
		$annotations = array('B-T', 'I-T', 'O');
		return '{'. implode( ',', $annotations ) .'}';
	}


	/** Gibt der Attribut-Wert für das jeweilige Token zurück
	 * @return mixed
	 */
	public function getValueForToken( $token, array $tokens, $currentTokensIndex, $Formatter ) {
		#return "O";
		$annotatedSentence = $Formatter->getAnnotatedSentence();
		$matches = array();
		preg_match_all( "/<T>(.+?)<\/T>/", $annotatedSentence, $matches );
		
	#	$sentenceBeginning = $match[1];
	#	$sentenceEnding = $match[3];
		
	#	$compressedSentenceBeginning = str_replace( ' ', '', $sentenceBeginning );
	#	$compressedSentenceEnding = str_replace( ' ', '', $sentenceEnding );

		$movieTitles = $matches[1];
		foreach( $movieTitles as $movieTitle ) {
			$movieTitleLength = count( explode( ' ', $movieTitle ) );
			$compressedMovieTitle = str_replace( ' ', '', $movieTitle );
	
			if( $movieTitleLength > 2 ) {
				$trigramm = array( self::getPreviousToken( $tokens, $currentTokensIndex ), $token, self::getNextToken( $tokens, $currentTokensIndex ) );
				$compressedTrigramm = implode( '', $trigramm );
				if( strpos( $compressedMovieTitle, $compressedTrigramm ) !== false )
					return 'I-T';

				$trigramm = array( $token, self::getNextToken( $tokens, $currentTokensIndex ), self::getNextToken( $tokens, $currentTokensIndex+1 ) );
				$compressedTrigramm = implode( '', $trigramm );
				if( strpos( $compressedMovieTitle, $compressedTrigramm ) !== false )
					return 'B-T';
		
				$trigramm = array( self::getPreviousToken( $tokens, $currentTokensIndex-1 ), self::getPreviousToken( $tokens, $currentTokensIndex ), $token );
				$compressedTrigramm = implode( '', $trigramm );
				if( strpos( $compressedMovieTitle, $compressedTrigramm ) !== false )
					return 'I-T';
			}
			elseif( $movieTitleLength == 2 ) {
				$trigramm = array( $token, self::getNextToken( $tokens, $currentTokensIndex ) );
				$compressedTrigramm = implode( '', $trigramm );
				if( strpos( $compressedMovieTitle, $compressedTrigramm ) !== false )
					return 'B-T';
				
				$trigramm = array( self::getPreviousToken( $tokens, $currentTokensIndex ), $token );
				$compressedTrigramm = implode( '', $trigramm );
				if( strpos( $compressedMovieTitle, $compressedTrigramm ) !== false )
					return 'I-T';
			}
			elseif( $movieTitleLength == 1 ) {
				if( $movieTitle == $token )
					return 'B-T';
			}
		}
			
		return 'O';
	}


	/** Fügt Tokens zu einem Satz ohne Leerzeichen zusammen
	 * @return string
	 */
	public static function joinTokens( array $tokens, $startIndex=0, $endIndex=null ) {
		if( $endIndex === null )
			$endIndex = count( $tokens );
		$joined = array();
		$i = $startIndex;
		while( $i < $endIndex ) {
			$joined[] = $tokens[ $i ];
			$i++;
		}
		return implode( '', $joined );
	}


}