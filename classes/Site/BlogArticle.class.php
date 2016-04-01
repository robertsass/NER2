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
interface BlogArticleInterface {

	public static function createArticle( Site $Site );

	public static function getArticleById( $articleId );

	public static function getArticlesBySite( Site $Site, $limit, $start, $onlyPublished );

	public function addPhoto( \rsCore\File $File );

	public function getVersion( $languageCodeOrInstance, $createIfDoesNotExist=true );
	public function getSection( $languageCodeOrInstance );
	public function getTitle( $languageCodeOrInstance );
	public function getSubtitle( $languageCodeOrInstance );
	public function getTeaser( $languageCodeOrInstance );
	public function getText( $languageCodeOrInstance );

	public function getFirstPhoto();

	public function getPhotos();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class BlogArticle extends \rsCore\DatabaseDatasetAbstract implements BlogArticleInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'blog-articles';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt ein neues Article an
	 * @return BlogArticle
	 * @api
	 */
	public static function createArticle( Site $Site ) {
		$Article = self::create();
		if( $Article ) {
			$Article->siteId = $Site->getPrimaryKeyValue();
			$Article->date = time();
			$Article->adopt();
		}
		return $Article;
	}


	/** Gibt ein BlogArticle anhand seiner ID zurück
	 * @param integer $albumId
	 * @return BlogArticle
	 * @api
	 */
	public static function getArticleById( $articleId ) {
		return self::getByPrimaryKey( intval( $articleId ) );
	}


	/** Gibt alle Alben einer Site zurück
	 * @param Site $Site
	 * @return array Array von BlogArticle-Instanzen
	 * @api
	 */
	public static function getArticlesBySite( Site $Site, $limit=null, $start=0, $onlyPublished=false ) {
		$condition = '`siteId` = "'. $Site->getPrimaryKeyValue() .'"';
		if( $onlyPublished )
			$condition .= ' AND `visibility` = "public"';
		$condition .= ' ORDER BY `date` DESC';
		if( $limit !== null )
			$condition .= ' LIMIT '. intval($start) .','. intval($limit);
		return self::getAll( $condition );
	}


/* Public methods */

	/** Fügt diesem Article ein Foto hinzu
	 * @return Photo
	 * @api
	 */
	public function addPhoto( \rsCore\File $File ) {
		return BlogArticlePhoto::addPhoto( $this, $File );
	}


	/** Gibt den Version-Datensatz dieses Articles zurück
	 * @return BlogArticleVersion
	 * @api
	 */
	public function getVersion( $languageCodeOrInstance=null, $createIfDoesNotExist=true ) {
		return BlogArticleVersion::getVersionByArticle( $this, $languageCodeOrInstance, $createIfDoesNotExist );
	}


	/** Gibt die Rubrik dieses Articles zurück
	 * @return string
	 * @api
	 */
	public function getSection( $languageCodeOrInstance=null ) {
		$Version = $this->getVersion( $languageCodeOrInstance );
		if( $Version )
			return $Version->section;
		return null;
	}


	/** Gibt den Titel dieses Articles zurück
	 * @return string
	 * @api
	 */
	public function getTitle( $languageCodeOrInstance=null ) {
		$Version = $this->getVersion( $languageCodeOrInstance );
		if( $Version )
			return $Version->title;
		return null;
	}


	/** Gibt den Subtitel dieses Articles zurück
	 * @return string
	 * @api
	 */
	public function getSubtitle( $languageCodeOrInstance=null ) {
		$Version = $this->getVersion( $languageCodeOrInstance );
		if( $Version )
			return $Version->subtitle;
		return null;
	}


	/** Gibt den Teaser dieses Articles zurück
	 * @return string
	 * @api
	 */
	public function getTeaser( $languageCodeOrInstance=null ) {
		$Version = $this->getVersion( $languageCodeOrInstance );
		if( $Version )
			return $Version->teaser;
		return null;
	}


	/** Gibt den Text dieses Articles zurück
	 * @return string
	 * @api
	 */
	public function getText( $languageCodeOrInstance=null ) {
		$Version = $this->getVersion( $languageCodeOrInstance );
		if( $Version )
			return $Version->text;
		return null;
	}


	/** Gibt das erste Foto dieses Articles zurück
	 * @return Photo
	 * @api
	 */
	public function getFirstPhoto() {
		$condition = '`articleId` = "'. intval( $this->getPrimaryKeyValue() ) .'"';
		$condition .= ' ORDER BY `id` ASC';
		$condition .= ' LIMIT 0,1';
		$photos = BlogArticlePhoto::getAll( $condition );
		if( is_array( $photos ) )
			return current( $photos );
		return $photos;
	}


	/** Gibt die Datei-Relationen dieses Articles zurück
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public function getPhotos() {
		return BlogArticlePhoto::getFilesByArticle( $this );
	}


	/** Löscht das BlogArticle und entfernt es aus allen Alben
	 * @param string $locationCodeOrInstance
	 * @return string
	 * @api
	 * @todo Auch aus allen Alben entfernen
	 */
	public function remove() {
		parent::remove();
	}


/* Private methods */

	protected function encodeDate( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


	protected function decodeDate( $value ) {
		return \rsCore\DatabaseConnector::decodeDatetime( $value );
	}


}