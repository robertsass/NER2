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
interface DocumentTagInterface {

	static function add( $name, $language, $documentIdOrInstance );
	static function getByDocument( $documentIdOrInstance, $language );
	static function getTagsByDocument( $documentIdOrInstance, $language );
	static function removeTagFromDocument( $tagNameOrInstance, $documentIdOrInstance, $language );

	function getTag();
	function getName();
	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DocumentTag extends \rsCore\DatabaseDatasetAbstract implements DocumentTagInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-document-tags';


	/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


	/* Private methods */

	protected static function getDocumentByParameter( $documentIdOrInstance ) {
		if( is_object( $documentIdOrInstance ) && $documentIdOrInstance instanceof Document ) {
			return $documentIdOrInstance;
		}
		elseif( is_int( $documentIdOrInstance ) || intval( $documentIdOrInstance ) > 0 ) {
			return Document::getById( intval( $documentIdOrInstance ) );
		}
		return null;
	}


	/* Static methods */

	/** Weist einem Dokument einen neuen Tag zu
	 * @param string $name Name des Tags
	 * @param string $language Sprache
	 * @param mixed $documentIdOrInstance
	 * @return DocumentTag-Objekt
	 * @api
	 */
	public static function add( $name, $language, $documentIdOrInstance ) {
		$Document	= self::getDocumentByParameter( $documentIdOrInstance );
		$Tag		= Tag::getTagByName( $name, $language );
		$documentId	= $Document->getPrimaryKeyValue();
		$tagId		= $Tag->getPrimaryKeyValue();

		$DocumentTag = self::getByColumns(
			array(
				'documentId' => $documentId,
				'tagId' => $tagId,
			)
		);

		if( !$DocumentTag ) {
			$DocumentTag = self::create();
			if( $DocumentTag ) {
				$DocumentTag->documentId = $documentId;
				$DocumentTag->tagId = $tagId;
				$DocumentTag->adopt();
			}
		}
		return $DocumentTag;
	}


	/** Findet alle Tags eines Dokuments
	 * @param mixed $documentIdOrInstance
	 * @param string $language Sprache
	 * @return array Array von DocumentTag-Objekten
	 * @api
	 */
	public static function getByDocument( $documentIdOrInstance, $language=null ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		$tags = self::getByColumn( 'documentId', $Document->getPrimaryKeyValue(), true );
		if( $language !== null ) {
			foreach( $tags as $index => $DocumentTag ) {
				if( $DocumentTag->getTag()->language != $language )
					unset( $tags[ $index ] );
			}
		}
		return $tags;
	}


	/** Findet alle Tags eines Dokuments und gibt nur die Tag-Objekte zurück
	 * @param mixed $documentIdOrInstance
	 * @param string $language Sprache
	 * @return array Array von Tag-Objekten
	 * @api
	 */
	public static function getTagsByDocument( $documentIdOrInstance, $language=null ) {
		$tags = array();
		foreach( self::getByDocument( $documentIdOrInstance ) as $DocumentTag ) {
			$Tag = $DocumentTag->getTag();
			if( $Tag ) {
				if( $language === null || $Tag->language == $language )
					$tags[] = $Tag;
			}
			else	// Datenbanksäuberung: falls der zugeordnete Tag selbst nicht mehr existiert wird auch die Verknüpfung gelöst
				$DocumentTag->remove();
		}
		return $tags;
	}


	/** Löst die Verknüpfung zwischen einem Tag und einem Dokument
	 * @param mixed $tagNameOrInstance
	 * @param mixed $documentIdOrInstance
	 * @param string $language Sprache
	 * @return array Array von Tag-Objekten
	 * @api
	 */
	public static function removeTagFromDocument( $tagNameOrInstance, $documentIdOrInstance, $language=null ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		if( $Document && $language === null )
			$language = $Document->getLanguage();
		else
			throw new Exception( "No valid Document instance." );
		if( is_object( $tagNameOrInstance ) && $tagNameOrInstance instanceof Tag )
			$Tag = $tagNameOrInstance;
		elseif( is_string( $tagNameOrInstance ) )
			$Tag = Tag::getTagByName( $tagNameOrInstance, $language, false );
		else
			throw new Exception( "First parameter must be a Tag-Instance or it's name as a string." );
		if( !$Document || !$Tag )
			return null;

		$DocumentTag = self::getByColumns( array(
			'documentId' => $Document->getPrimaryKeyValue(),
			'tagId'	=> $Tag->getPrimaryKeyValue()
		), true );
		if( $DocumentTag )
			return $DocumentTag->remove();
		return null;
	}


	/** Beim Casten zum String gebe den Tag-Namen zurück
	 * @return string
	 */
	public function __toString() {
		return $this->getName();
	}


	/** Gibt die zugehörige Tag-Instanz zurück
	 * @return Tag
	 * @api
	 */
	public function getTag() {
		return Tag::getByPrimaryKey( $this->tagId );
	}


	/** Gibt den Namen des Tags zurück
	 * @return string
	 * @api
	 */
	public function getName() {
		return $this->getTag()->name;
	}


	/** Entfernt den Tag vom Dokument
	 * @return boolean
	 * @api
	 */
	public function remove() {
		return parent::remove();
	}


}