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
interface TagInterface {

	static function getTagByName( $name, $language, $createIfDoesNotExist );

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Tag extends \rsCore\DatabaseDatasetAbstract implements TagInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-tags';


	/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


	/* Static methods */

	/** Findet einen Tag beim Namen oder erzeugt einen neuen Tag
	 * @param string $name
	 * @param string $language
	 * @return Tag
	 * @api
	 */
	public static function getTagByName( $name, $language, $createIfDoesNotExist=true ) {
		$name = trim( $name );
		$Tag = self::getByColumns( array('name' => $name, 'language' => $language) );
		if( !$Tag && $createIfDoesNotExist ) {
			$Tag = self::create();
			if( $Tag ) {
				$Tag->name = $name;
				$Tag->language = $language;
				$Tag->adopt();
			}
		}
		return $Tag;
	}


	/** Entfernt den Tag
	 * @return boolean
	 * @api
	 * @todo Also remove all referencing datasets from table `brainstage-document-tags`, etc.
	 */
	public function remove() {
		return parent::remove();
	}


}