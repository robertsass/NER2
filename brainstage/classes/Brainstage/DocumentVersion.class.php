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
interface DocumentVersionInterface {

	static function createVersion( $documentIdOrInstance, $language, $name );
	static function getByIdAndDocument( $documentIdOrInstance, $versionId );
	static function getVersionsByDocument( $documentIdOrInstance, $language );
	static function getByDocument( $documentIdOrInstance, $language );
	static function getDocumentsLanguages( $documentIdOrInstance );
	static function getDocumentsLastEditedVersion( $documentIdOrInstance );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class DocumentVersion extends \rsCore\DatabaseDatasetAbstract implements DocumentVersionInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'brainstage-document-versions';


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


	/** Bei Änderungen wird der Timestamp angepasst
	 * @return void
	 * @internal
	 */
	protected function onChange() {
		$this->timestamp = time();
	}


	/* Static methods */

	/** Legt eine neue Version an
	 * @param mixed $documentIdOrInstance
	 * @param string $language
	 * @param string $name
	 * @return DocumentVersion
	 * @api
	 */
	public static function createVersion( $documentIdOrInstance, $language, $name=null ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		$NewVersion = self::create();
		if( $NewVersion ) {
			$NewVersion->documentId = $Document->getPrimaryKeyValue();
			$NewVersion->language = $language;
			$NewVersion->name = $name;
			$NewVersion->content = '';
			$NewVersion->userId = user()->getPrimaryKeyValue();
			$NewVersion->adopt();
		}
		return $NewVersion;
	}


	/** Selektiert eine Dokument-Version anhand des Dokuments und einer ID
	 * @param mixed $documentIdOrInstance
	 * @param integer $versionId
	 * @return DocumentVersion
	 * @api
	 */
	public static function getByIdAndDocument( $documentIdOrInstance, $versionId ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		return self::getByColumns(
			array(
				'documentId' => $Document->getPrimaryKeyValue(),
				'id' => intval( $versionId )
			)
		);
	}


	/** Gibt alle Versionen eines Dokuments zurück
	 * @param mixed $documentIdOrInstance
	 * @param string $language
	 * @return array Array von DocumentVersion-Instanzen
	 * @api
	 */
	public static function getVersionsByDocument( $documentIdOrInstance, $language=null ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		$condition = array( 'documentId' => $Document->getPrimaryKeyValue() );
		if( $language )
			$condition['language'] = $language;
		return self::getByColumns( $condition, true );
	}


	/** Selektiert einen Dokument-Inhalt anhand des Dokuments und einer Sprache
	 * @param mixed $documentIdOrInstance
	 * @param string $language
	 * @return DocumentVersion
	 * @api
	 */
	public static function getByDocument( $documentIdOrInstance, $language ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		return self::getByColumns(
			array(
				'documentId' => $Document->getPrimaryKeyValue(),
				'language' => $language
			),
			false,
			array('timestamp' => 'DESC')
		);
	}


	/** Gibt die Sprachen zurück, in denen das Dokument vorliegt
	 * @param mixed $documentIdOrInstance
	 * @return array Sprachkürzel
	 * @api
	 */
	public static function getDocumentsLanguages( $documentIdOrInstance ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		$sql = 'SELECT DISTINCT `language` FROM `%TABLE` WHERE `documentId` = "'. $Document->getPrimaryKeyValue() .'"';
		$languages = array();
		foreach( self::getDatabaseConnection()->get( $sql ) as $Version )
			$languages[ $Version->language ] = Language::getLanguageByShortCode( $Version->language );
		return $languages;
	}


	/** Gibt die zuletzt bearbeitete Version dieses Dokuments zurück
	 * @param mixed $documentIdOrInstance
	 * @return DocumentVersion
	 * @api
	 */
	public static function getDocumentsLastEditedVersion( $documentIdOrInstance ) {
		$Document = self::getDocumentByParameter( $documentIdOrInstance );
		return self::getByColumns(
			array(
				'documentId' => $Document->getPrimaryKeyValue()
			),
			false,
			array('timestamp' => 'DESC')
		);
	}


}