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
class StringUtils {


	public static function contains( $haystack, $needle, $translateNeedle=false ) {
		if( $translateNeedle )
			$needle = rsDictionary::t( $needle );
		return false !== strpos( strtolower( $haystack ), strtolower( $needle ) );
	}


	public static function containsButIsNotEqual( $haystack, $needle, $translateNeedle=false ) {
		if( $translateNeedle )
			$needle = rsDictionary::t( $needle );
		return false !== strpos( strtolower( $haystack ), strtolower( $needle ) ) && strtolower( $haystack ) !== strtolower( $needle );
	}


	public static function containsOne( $haystack, array $needles, $translateNeedle=false ) {
		foreach( $needles as $needle ) {
			if( self::contains( $haystack, $needle, $translateNeedle ) )
				return $needle;
		}
		return false;
	}


	public static function containsOneButIsNotEqual( $haystack, array $needles, $translateNeedle=false ) {
		foreach( $needles as $needle ) {
			if( self::containsButIsNotEqual( $haystack, $needle, $translateNeedle ) )
				return $needle;
		}
		return false;
	}


	public static function containsAll( $haystack, array $needles, $translateNeedle=false ) {
		foreach( $needles as $needle ) {
			if( !self::contains( $haystack, $needle, $translateNeedle ) )
				return false;
		}
		return true;
	}


	public static function containsAllButIsNotEqual( $haystack, array $needles, $translateNeedle=false ) {
		foreach( $needles as $needle ) {
			if( !self::containsButIsNotEqual( $haystack, $needle, $translateNeedle ) )
				return false;
		}
		return true;
	}


	public static function calculateDifference( $string1, $string2 ) {
		return levenshtein( $string1, $string2 );
	#	$score = 0;
	#	similar_text( $string1, $string2, $score );
	#	return $score;
	}


	public static function calculateSimilarity( $string1, $string2 ) {
		$score = 0;
		similar_text( $string1, $string2, $score );
		return $score;
	}


	public static function stripLinks( $text ) {
		return preg_replace( '#<\s*a(\s+.*?>|>).*?<\s*/\s*a\s*>#', '', $text );
	}


	public static function stripTags( $text, $removeLinks=false ) {
		return strip_tags( $removeLinks ? self::stripLinks($text) : $text );
	}


	public static function getPlainText( $text ) {
		$clean = $text;
		$clean = strip_tags( $clean );
		$clean = html_entity_decode( $clean );
		return trim( $clean );
	}


	public static function getTeaser( $text, $characterMaximum, $wordMaximum=null, $sentenceMaximum=null, $strict=false, $suffix="..." ) {
		$text = trim( $text );
		$teaser = '';

	#	$characters = strlen( $text );
		$sentences = explode( '/SENTENCE_END/', preg_replace( '#([\.\!¡\?¿])#', '${1}/SENTENCE_END/', $text ) );
		$sentenceCount = count( $sentences );
		$wordCount = 0;
		$characterCount = 0;

		foreach( $sentences as $sIndex => $sentence ) {
			$sentence = trim( $sentence );
			if( $sentenceMaximum === null || $sIndex < $sentenceMaximum  ) {

				$words = explode( '/WORD_END/', preg_replace( '#([ ,;:])#', '${1}/WORD_END/', $sentence ) );
				foreach( $words as $wIndex => $word ) {
					if( $wordMaximum === null || $wordCount < $wordMaximum ) {

						$wordLength = strlen( $word );
						if( $characterMaximum === null || ($characterCount+$wordLength) < $characterMaximum ) {
							$wordCount++;
							$characterCount += $wordLength;
							$teaser .= $word;
						}
						else {
							$teaser = trim( $teaser ) . $suffix;
							return $teaser;
						}

					}
					else {
						break;
					}
				}

			}
			else {
				$teaser .= ' ';
				break;
			}
		}

		if( $characterCount < strlen($text) )
			$teaser = $teaser . $suffix;
		return $teaser;
	}


	public static function extract( $string, array $interval ) {
		$p = $string;
		foreach( $interval as $left => $right ) {
			$p = explode( $left, $p, 2 );
			$p = explode( $right, $p[1], 2 );
			$p = $p[0];
		}
		return $p;
	}


}