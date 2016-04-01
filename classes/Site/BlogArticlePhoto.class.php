<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Site;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface BlogArticlePhotoInterface {

	public static function addPhoto( BlogArticle $Article, \rsCore\File $File );

	public static function getRelation( BlogArticle $Article, \rsCore\File $File );
	public static function getRelationsByFile( \rsCore\File $File );
	public static function getRelationsByArticle( BlogArticle $Article );
	public static function getArticlesByFile( \rsCore\File $File );
	public static function getFilesByArticle( BlogArticle $Article );

	public function getArticle();
	public function getFile();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class BlogArticlePhoto extends \rsCore\DatabaseDatasetAbstract implements BlogArticlePhotoInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'blog-article-photos';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return Photo
	 * @api
	 */
	public static function addPhoto( BlogArticle $Article, \rsCore\File $File ) {
		$Photo = self::create();
		if( $Photo ) {
			$Photo->articleId = $Article->getPrimaryKeyValue();
			$Photo->fileId = $File->getPrimaryKeyValue();
			$Photo->adopt();
		}
		return $Photo;
	}


	/** Gibt eine BlogArticle-File-Relation anhand eines Articles und einer Datei zurück
	 * @param BlogArticle $Article
	 * @param \rsCore\File $File
	 * @return Photo
	 * @api
	 */
	public static function getRelation( BlogArticle $Article, \rsCore\File $File ) {
		return self::getByColumns( array('articleId' => $Article->getPrimaryKeyValue(), 'fileId' => $File->getPrimaryKeyValue()), false );
	}


	/** Gibt alle Relationen zu einer Datei zurück
	 * @param \rsCore\File $File
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public static function getRelationsByFile( \rsCore\File $File ) {
		return self::getByColumn( 'fileId', $File->getPrimaryKeyValue(), true );
	}


	/** Gibt alle Relationen zu einem Article zurück
	 * @param BlogArticle $Article
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public static function getRelationsByArticle( BlogArticle $Article ) {
		return self::getByColumn( 'articleId', $Article->getPrimaryKeyValue(), true );
	}


	/** Gibt alle Alben zurück, in denen ein Foto enthalten ist
	 * @param \rsCore\File $File
	 * @return array Array von BlogArticle-Instanzen
	 * @api
	 */
	public static function getArticlesByFile( \rsCore\File $File ) {
		$articles = array();
		foreach( self::getRelationsByFile( $File ) as $Relation )
			$articles[] = $Relation->getArticle();
		return $articles;
	}


	/** Gibt alle Fotos eines Articles zurück
	 * @param BlogArticle $Article
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public static function getFilesByArticle( BlogArticle $Article ) {
		$files = array();
		foreach( self::getRelationsByArticle( $Article ) as $Relation )
			$files[] = $Relation->getFile();
		return $files;
	}


	/** Gibt alle Fotos eines Articles zurück
	 * @param BlogArticle $Article
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public static function getFilesBySite( Site $Site, $limit=null, $start=0, $sortDescending=false ) {
		$files = array();
		foreach( self::getRelationsBySite( $Site, $limit, $start, $sortDescending ) as $Relation )
			$files[] = $Relation->getFile();
		return $files;
	}


/* Public methods */

	/** Gibt das Article zurück
	 * @return BlogArticle
	 * @api
	 */
	public function getArticle() {
		return BlogArticle::getByPrimaryKey( $this->articleId );
	}


	/** Gibt die Datei zurück
	 * @return \rsCore\File
	 * @api
	 */
	public function getFile() {
		return \rsCore\File::getByPrimaryKey( $this->fileId );
	}


	/** Löscht das Photo und entfernt es aus allen Alben
	 * @param string $locationCodeOrInstance
	 * @return string
	 * @api
	 * @todo Auch aus allen Alben entfernen
	 */
	public function remove() {
		parent::remove();
	}


}