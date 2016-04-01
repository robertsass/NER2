<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface InternalDictionaryInterface {

	function getLanguage();
	function getLanguageCode();
	function getTranslation( $key, $comment="" );

	function get( $key, $comment="" );
	function set( $key, $translation, $comment="" );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class InternalDictionary extends \rsCore\Dictionary implements InternalDictionaryInterface {


	protected static $_databaseTable = 'brainstage-internal-translation';


	public function getTranslation( $key, $comment="" ) {
		return \Brainstage\InternalDictionaryTranslation::getTranslation( $this->getLanguage(), $key, $comment );
	}


}