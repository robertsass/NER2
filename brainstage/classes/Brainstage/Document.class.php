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
interface DocumentInterface {

	static function createDocument( $parentNodeInstanceOrId );
	static function getDocumentByColumn( $language, $column, $value, $allowMultipleResults, $sorting, $limit );
	static function getDocumentByColumns( $language, $columns, $allowMultipleResults, $sorting, $limit );
	static function getDocumentById( $id, $language );
	static function getDocumentByPrimaryKey( $primaryKey, $language );

	function setLanguage( $language );
	function getLanguage();
	function getLanguages();
	function getLastEditedVersionsLanguage();

	function getVersions( $language );
	function getVersion( $versionId );
	function getCurrentVersion();

	function getName();
	function getContent();
	function getTemplateName();
	
	function getTags();

	function getPathComponent( $language );
	function getComposedPath( $language );
	function getComposedUrl();

	function addTag( $name );
	function removeTag( $name );
	function newVersion( $language );
	function removeDocument();

	function getArray();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Document extends \rsCore\DatabaseNestedSetDatasetAbstract implements DocumentInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'brainstage-document-tree';

	protected $_language;
	protected $_languages;
	protected $_currentVersion;
	protected $_template;


	/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


	/* Static methods */

	/** Fügt ein neues Dokument in den NestedSet ein
	 * @param mixed $parentNodeInstanceOrId
	 * @return Document Document-Instanz oder null
	 * @api
	 */
	public static function createDocument( $parentNodeInstanceOrId ) {
		try {
			if( is_object( $parentNodeInstanceOrId ) )
				$ParentNode = $parentNodeInstanceOrId;
			else
				$ParentNode = self::getById( $parentNodeInstanceOrId );
			return $ParentNode->createChild();
		} catch( Exception $Exception ) {
			return null;
		}
	}


	/** Sucht ein Dokument anhand einer Spalte
	 * @param string $language Das Sprachkürzel
	 * @param string $column Spalte
	 * @param string $value Sollwert
	 * @param boolean $allowMultipleResults
	 * @param array $sorting Spalten und ihre Ordnung (ASC/DESC)
	 * @param int $limit Maximale Anzahl
	 * @return mixed Document-Instanz oder Array von Instanzen
	 * @api
	 */
	public static function getDocumentByColumn( $language, $column, $value, $allowMultipleResults, $sorting, $limit ) {
		$Instance = static::getByColumn( $column, $value, $allowMultipleResults, $sorting, $limit );
		if( $Instance )
			$Instance->setLanguage( $language );
		return $Instance;
	}


	/** Sucht ein Dokument anhand diverser Spalten
	 * @param string $language Das Sprachkürzel
	 * @param array $columns Spaltennamen und ihre Sollwerte
	 * @param boolean $allowMultipleResults
	 * @param array $sorting Spalten und ihre Ordnung (ASC/DESC)
	 * @param int $limit Maximale Anzahl
	 * @return mixed Document-Instanz oder Array von Instanzen
	 * @api
	 */
	public static function getDocumentByColumns( $language, $columns, $allowMultipleResults, $sorting, $limit ) {
		$Instance = static::getByColumns( $columns, $allowMultipleResults, $sorting, $limit );
		if( $Instance )
			$Instance->setLanguage( $language );
		return $Instance;
	}


	/** Sucht ein Dokument anhand seiner ID (faktisch kein Unterschied zu getByPrimaryKey())
	 * @param int $id Die ID
	 * @param string $language Das Sprachkürzel
	 * @return Document
	 * @api
	 */
	public static function getDocumentById( $id, $language ) {
		$Instance = static::getById( $id );
		if( $Instance )
			$Instance->setLanguage( $language );
		return $Instance;
	}


	/** Sucht ein Dokument anhand seines Primärschlüssels
	 * @param int $primaryKey Der Primärschlüssel
	 * @param string $language Das Sprachkürzel
	 * @return Document
	 * @api
	 */
	public static function getDocumentByPrimaryKey( $primaryKey, $language ) {
		$Instance = static::getByPrimaryKey( $primaryKey );
		if( $Instance )
			$Instance->setLanguage( $language );
		return $Instance;
	}


	/** Konstruktor
	 * @return void
	 * @api
	 */
	protected function init() {
		parent::init();
		$this->setLanguage( $this->getLastEditedVersionsLanguage() );
	}


	/** Legt die Sprache fest, für die die Inhalte dieses Dokuments geladen werden sollen
	 * @param string $language Das Sprachkürzel
	 * @return boolean
	 * @api
	 */
	public function setLanguage( $language ) {
		if( $language === null || !array_key_exists( $language, $this->getLanguages() ) ) {
			$languageCode = \rsCore\Localization::extractLanguageCode( $language );
			if( array_key_exists( $languageCode, $this->getLanguages() ) )
				$language = $languageCode;
			else
				return false;
		}
		$this->_language = $language;
		return true;
	}


	/** Gibt die ausgewählte Sprache zurück
	 * @return string
	 * @api
	 */
	public function getLanguage() {
		return $this->_language;
	}


	/** Gibt die Sprache zurück, in denen dieses Dokument vorliegt
	 * @return array
	 * @api
	 */
	public function getLanguages() {
		if( !$this->_languages )
			$this->_languages = DocumentVersion::getDocumentsLanguages( $this );
		return $this->_languages;
	}


	/** Legt die Sprache fest, für die die Inhalte dieses Dokuments geladen werden sollen
	 * @param string $language Das Sprachkürzel
	 * @return boolean
	 * @api
	 */
	public function getLastEditedVersionsLanguage() {
		$LastEditedVersion = DocumentVersion::getDocumentsLastEditedVersion( $this );
		return $LastEditedVersion ? $LastEditedVersion->language : null;
	}


	/** Gibt alle Versionen dieses Dokuments zurück
	 * @param string|null $language
	 * @return array Array von DocumentVersion-Instanzen
	 * @api
	 */
	public function getVersions( $language=null ) {
		return DocumentVersion::getVersionsByDocument( $this, $language );
	}


	/** Gibt eine Version anhand seiner ID zurück
	 * @param integer $versionId
	 * @return DocumentVersion
	 * @api
	 */
	public function getVersion( $versionId=null ) {
		if( $versionId === null )
			return $this->getCurrentVersion();
		return DocumentVersion::getByIdAndDocument( $this, $versionId );
	}


	/** Gibt die aktuellste Version des Dokument-Datensatzes zurück
	 * @return DocumentVersion
	 * @api
	 */
	public function getCurrentVersion() {
		if( !$this->_currentVersion ) {
			$this->_currentVersion = DocumentVersion::getByDocument( $this, $this->_language );
		}
		return $this->_currentVersion;
	}


	/** Gibt den Namen des Dokuments zurück
	 * @return string
	 * @api
	 */
	public function getName() {
		$CurrentVersion = $this->getCurrentVersion();
		if( $CurrentVersion )
			return $CurrentVersion->name;
		return null;
	}


	/** Gibt den Inhalt des Dokuments zurück
	 * @return string
	 * @api
	 */
	public function getContent() {
		$CurrentVersion = $this->getCurrentVersion();
		if( $CurrentVersion )
			return $CurrentVersion->content;
		return null;
	}


	/** Gibt eine Instanz des zugehörigen Templates zurück
	 * @return object
	 * @api
	 */
	public function getTemplateName() {
		return $this->templateName;
	}


	/** Gibt die Tags des Dokuments zurück
	 * @return array Array von DocumentTag-Instanzen
	 * @api
	 */
	public function getTags() {
		return DocumentTag::getByDocument( $this, $this->getLanguage() );
	}


	/** Gibt den Pfad-Komponenten des Dokuments zurück
	 * @param string $language
	 * @return array Array von DocumentTag-Instanzen
	 * @api
	 */
	public function getPathComponent( $language=null ) {
		if( $language === null )
			$language = $this->getLanguage();
		return DocumentPathComponent::getByDocument( $this, $language );
	}


	/** Gibt den aus den Pfad-Komponenten aller Elterndokumente zusammengefügten Pfad zurück
	 * @param string $language
	 * @return string
	 * @api
	 */
	public function getComposedPath( $language=null ) {
		if( $language === null )
			$language = $this->getLanguage();
		$nodes = array_reverse( array_merge( array( $this ), $this->getParents() ) );
		$path = array();
		foreach( $nodes as $Node ) {
			$pathComponent = $Node->getPathComponent( $language );
			if( $pathComponent )
				$path[] = strval( $pathComponent );
		}
		return implode( '/', $path );
	}


	/** Gibt die aus den URL-Rules aller Elterndokumente zusammengefügte URL zurück
	 * @return string
	 * @api
	 */
	public function getComposedUrl() {
		$nodes = array_reverse( array_merge( array( $this ), $this->getParents() ) );
		$RequestPath = \rsCore\Core::core()->getRequestPath();
		$composedUrl = array();

		$pathBase = \rsCore\Core::getSiteUrl();	

		$Rule = \rsCore\RequestHandlerRule::getRule( $this );
		if( $Rule && false ) {
		}
		else {
			$path = $this->getComposedPath();
			if( $path )
				return $path;
			
			return $pathBase .'/?d='. $this->getPrimaryKeyValue();
		}

		if( !array_key_exists( 'domainname', $composedUrl ) )
			$composedUrl['domainname'] = $RequestPath->domain->domainbase;
		if( !array_key_exists( 'subdomains', $composedUrl ) )
			$composedUrl['subdomains'] = $RequestPath->domain->subdomains;
		if( !array_key_exists( 'path', $composedUrl ) )
			$composedUrl['path'] = $RequestPath->path;

		$subdomains = is_array( $composedUrl['subdomains'] ) ? join( '.', $composedUrl['subdomains'] ) : $composedUrl['subdomains'];
		$domainname = is_array( $composedUrl['domainname'] ) ? join( '.', $composedUrl['domainname'] ) : $composedUrl['domainname'];
		$path = ltrim( is_array( $composedUrl['path'] ) ? join( '/', $composedUrl['path'] ) : $composedUrl['path'], '/' );
		return $RequestPath->scheme .'://'. ($subdomains ? $subdomains .'.' : '') . $domainname .'/'. $path;
	}


	/** Fügt dem Dokument ein Tag hinzu
	 * @param string $name
	 * @return DocumentTag
	 * @api
	 */
	public function addTag( $name ) {
		return DocumentTag::add( $name, $this->getLanguage(), $this );
	}


	/** Entfernt einen Tag von dem Dokument
	 * @param string $name
	 * @return boolean
	 * @api
	 */
	public function removeTag( $name ) {
		return DocumentTag::removeTagFromDocument( $name, $this, $this->getLanguage() );
	}


	/** Erzeugt eine neue Version und gibt diese zurück
	 * @param string $language
	 * @return DocumentVersion
	 * @api
	 */
	public function newVersion( $language ) {
		return DocumentVersion::createVersion( $this, $language, $this->getName() );
	}


	/** Löscht das Dokument mitsamt aller Zugehörigkeiten (Tagzuordnungen, Versionen, URL-Zuordnungen, ...)
	 * @param string $language
	 * @return boolean
	 * @api
	 * @todo Implementieren! Lösche alle Versionen, Tags und alle Referenzen auf dieses Dokument (URL-Targets usw.)
	 */
	public function removeDocument() {
		$success = true;
		foreach( $this->getVersions() as $Version )
			$success = $success && $Version->remove();
		foreach( $this->getTags() as $Tag )
			$success = $success && $Tag->remove();
		foreach( \rsCore\RequestHandlerRule::getRule( $this ) as $UrlRule ) {
			foreach( $UrlRule->getPatterns() as $Pattern )
				$success = $success && $Pattern->remove();
			$success = $success && $UrlRule->remove();
		}
		return $success && $this->remove();
	}


	/** Gibt eine Array-Repräsentation zurück
	 * @return array
	 * @api
	 */
	public function getArray() {
		return $this->getColumns();
	}


}