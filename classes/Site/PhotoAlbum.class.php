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
interface PhotoAlbumInterface {

	public static function createAlbum();

	public static function getAlbumById( $albumId );

	public static function getAlbums( $limit, $start );
#	public static function getAlbumsBySite( Site $Site, $limit, $start );
#	public static function getAlbumsByEvent( Event $Event );
#	public static function getAlbumsByTimeframe( $startTimestamp, $endTimestamp );

	public function addPhoto( \rsCore\File $File );

	public function getMeta( $languageCodeOrInstance, $createIfDoesNotExist=true );
	public function getTitle( $languageCodeOrInstance );
	public function getDescription( $languageCodeOrInstance );

	public function getFirstPhoto();
	public function getRandomPhoto();

	public function getFiles();
	public function getPhotos();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class PhotoAlbum extends \rsCore\DatabaseDatasetAbstract implements PhotoAlbumInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'photo-albums';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt ein neues Album an
	 * @return PhotoAlbum
	 * @api
	 */
	public static function createAlbum() {
		$Album = self::create();
		if( $Album ) {
			$Album->date = time();
			$Album->adopt();
		}
		return $Album;
	}


	/** Gibt ein PhotoAlbum anhand seiner ID zurück
	 * @param integer $albumId
	 * @return PhotoAlbum
	 * @api
	 */
	public static function getAlbumById( $albumId ) {
		return self::getByPrimaryKey( intval( $albumId ) );
	}


	/** Gibt alle Alben zurück
	 * @param int $limit
	 * @param int $start
	 * @return array Array von PhotoAlbum-Instanzen
	 * @api
	 */
	public static function getAlbums( $limit=null, $start=0 ) {
		$condition = '1=1';
		$condition .= ' ORDER BY `date` DESC';
		if( $limit !== null )
			$condition .= ' LIMIT '. intval($start) .','. intval($limit);
		return self::getAll( $condition );
	}


/* Public methods */

	/** Fügt diesem Album ein Foto hinzu
	 * @return Photo
	 * @api
	 */
	public function addPhoto( \rsCore\File $File ) {
		return Photo::addPhoto( $this, $File );
	}


	/** Gibt den Meta-Datensatz dieses Albums zurück
	 * @return PhotoAlbumMeta
	 * @api
	 */
	public function getMeta( $languageCodeOrInstance=null, $createIfDoesNotExist=true ) {
		return PhotoAlbumMeta::getMetaByAlbum( $this, $languageCodeOrInstance, $createIfDoesNotExist );
	}


	/** Gibt den Namen dieses Albums zurück
	 * @return string
	 * @api
	 */
	public function getTitle( $languageCodeOrInstance=null ) {
		$Meta = $this->getMeta( $languageCodeOrInstance );
		if( $Meta )
			return $Meta->title;
		return null;
	}


	/** Gibt die Beschreibung dieses Albums zurück
	 * @return string
	 * @api
	 */
	public function getDescription( $languageCodeOrInstance=null ) {
		$Meta = $this->getMeta( $languageCodeOrInstance );
		if( $Meta ) {
			$description = $Meta->description;
			$paragraphs = array();
			foreach( explode( "\n", $description ) as $paragraph ) {
				$paragraph = trim( $paragraph );
				if( $paragraph != '' )
					$paragraphs[] = $paragraph;
			}
			$description = '<p>'. implode( '</p><p>', $paragraphs ) .'</p>';
			return $description;
		}
		return null;
	}


	/** Gibt das erste Foto dieses Albums zurück
	 * @return Photo
	 * @api
	 */
	public function getFirstPhoto() {
		$condition = '`albumId` = "'. intval( $this->getPrimaryKeyValue() ) .'"';
		$condition .= ' ORDER BY `id` ASC';
		$condition .= ' LIMIT 0,1';
		$photos = Photo::getAll( $condition );
		if( is_array( $photos ) )
			return current( $photos );
		return $photos;
	}


	/** Gibt ein zufälliges Foto dieses Albums zurück
	 * @return Photo
	 * @api
	 */
	public function getRandomPhoto() {
		$condition = '`albumId` = "'. intval( $this->getPrimaryKeyValue() ) .'"';
		$count = Photo::count( $condition );
		$random = mt_rand( 0, $count-1 );
		$condition .= ' ORDER BY `id` ASC';
		$condition .= ' LIMIT '. $random .',1';
		$photos = Photo::getAll( $condition );
		if( is_array( $photos ) )
			return current( $photos );
		return $photos;
	}


	/** Gibt die Dateien dieses Albums zurück
	 * @return array Array von \rsCore\File-Instanzen
	 * @api
	 */
	public function getFiles() {
		return Photo::getFilesByAlbum( $this );
	}


	/** Gibt die Datei-Relationen dieses Albums zurück
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public function getPhotos() {
		return Photo::getRelationsByAlbum( $this );
	}


	/** Löscht das PhotoAlbum und entfernt es aus allen Alben
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