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
interface DocumentPathComponentInterface {

	static function add( $documentIdOrInstance, $languageCodeOrInstance, $pathComponent );
	static function getByDocument( $documentIdOrInstance, $languageCodeOrInstance );
#	static function getTagsByDocument( $documentIdOrInstance, $language );
#	static function removeTagFromDocument( $tagNameOrInstance, $documentIdOrInstance, $language );

	function getDocument();
	function getLanguage();
	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DocumentPathComponent extends \rsCore\DatabaseDatasetAbstract implements DocumentPathComponentInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-document-path-components';


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
	 * @param string $languageCodeOrInstance Sprache
	 * @param string $pathComponent
	 * @return DocumentURL-Objekt
	 * @api
	 */
	public static function add( $documentIdOrInstance, $languageCodeOrInstance, $pathComponent ) {
		$Document	= self::getDocumentByParameter( $documentIdOrInstance );
		$documentId	= $Document->getPrimaryKeyValue();
		$Language	= Language::getLanguageInstance( $languageCodeOrInstance );
		$language	= $Language->shortCode;

		$Instance = self::getByColumns(
			array(
				'documentId' => $documentId,
				'language' => $language,
			)
		);

		if( !$Instance ) {
			$Instance = self::create();
			if( $Instance ) {
				$Instance->documentId = $documentId;
				$Instance->language = $language;
				$Instance->pathComponent = $pathComponent;
				$Instance->adopt();
			}
		}
		return $Instance;
	}


	/** Findet alle Tags eines Dokuments
	 * @param mixed $documentIdOrInstance
	 * @param string $languageCodeOrInstance Sprache
	 * @return array Array von DocumentURL-Objekten
	 * @api
	 */
	public static function getByDocument( $documentIdOrInstance, $languageCodeOrInstance=null ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		$documentId	= $Document->getPrimaryKeyValue();
		if( $languageCodeOrInstance )
			$Language = Language::getLanguageInstance( $languageCodeOrInstance );
		
		$parameters = array(
			'documentId' => $documentId
		);
		
		if( $Language )
			$parameters['language'] = $Language->shortCode;
		
		return self::getByColumns( $parameters, false );
	}


	/** Beim Casten zum String gebe den Pfad-Komponenten zurück
	 * @return string
	 */
	public function __toString() {
		return $this->pathComponent;
	}


	/** Gibt die zugehörige Document-Instanz zurück
	 * @return Document
	 * @api
	 */
	public function getDocument() {
		return Document::getByPrimaryKey( $this->documentId );
	}


	/** Gibt die zugehörige Language-Instanz zurück
	 * @return Language
	 * @api
	 */
	public function getLanguage() {
		return Language::getLanguageInstance( $this->language );
	}


	/** Entfernt die URL vom Dokument
	 * @return boolean
	 * @api
	 */
	public function remove() {
		return parent::remove();
	}


}