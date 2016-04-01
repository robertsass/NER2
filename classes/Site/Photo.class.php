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
interface PhotoInterface {

	public static function addPhoto( PhotoAlbum $Album, \rsCore\File $File );

	public static function getRelation( PhotoAlbum $Album, \rsCore\File $File );
	public static function getRelationsByFile( \rsCore\File $File );
	public static function getRelationsByAlbum( PhotoAlbum $Album );
	public static function getRelationsBySite( Site $Site, $limit, $start, $sortDescending );
	public static function getAlbumsByFile( \rsCore\File $File );
	public static function getFilesByAlbum( PhotoAlbum $Album );
	public static function getFilesBySite( Site $Site, $limit, $start, $sortDescending );

	public function getAlbum();
	public function getFile();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Photo extends \rsCore\DatabaseDatasetAbstract implements PhotoInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'photo-album-files';


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
	public static function addPhoto( PhotoAlbum $Album, \rsCore\File $File ) {
		$Photo = self::create();
		if( $Photo ) {
			$Photo->albumId = $Album->getPrimaryKeyValue();
			$Photo->fileId = $File->getPrimaryKeyValue();
			$Photo->adopt();
		}
		return $Photo;
	}


	/** Gibt eine PhotoAlbum-File-Relation anhand eines Albums und einer Datei zurück
	 * @param PhotoAlbum $Album
	 * @param \rsCore\File $File
	 * @return Photo
	 * @api
	 */
	public static function getRelation( PhotoAlbum $Album, \rsCore\File $File ) {
		return self::getByColumns( array('albumId' => $Album->getPrimaryKeyValue(), 'fileId' => $File->getPrimaryKeyValue()), false );
	}


	/** Gibt alle Relationen zu einer Datei zurück
	 * @param \rsCore\File $File
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public static function getRelationsByFile( \rsCore\File $File ) {
		return self::getByColumn( 'fileId', $File->getPrimaryKeyValue(), true );
	}


	/** Gibt alle Relationen zu einem Album zurück
	 * @param PhotoAlbum $Album
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public static function getRelationsByAlbum( PhotoAlbum $Album ) {
		return self::getByColumn( 'albumId', $Album->getPrimaryKeyValue(), true );
	}


	/** Gibt alle Relationen einer Site zurück
	 * @param PhotoAlbum $Album
	 * @return array Array von Photo-Instanzen
	 * @api
	 */
	public static function getRelationsBySite( Site $Site, $limit=null, $start=0, $sortDescending=false ) {
		$sql = array();
		$sql[] = 'SELECT album_files.*';
		$sql[] = 'FROM `'. PhotoAlbum::getDatabaseConnection()->getTable() .'` as albums, `'. self::getDatabaseConnection()->getTable() .'` as album_files';
		$sql[] = 'WHERE albums.`id` = album_files.`albumId` AND albums.`siteId` = "'. $Site->getPrimaryKeyValue() .'"';
		$sql[] = 'ORDER BY `id` '. ($sortDescending ? 'DESC' : 'ASC');
		if( $limit !== null )
			$sql[] = 'LIMIT '. intval($start) .','. intval($limit);
		$sql = implode( ' ', $sql );
		return self::getDatabaseConnection()->get( $sql );
	}


	/** Gibt alle Alben zurück, in denen ein Foto enthalten ist
	 * @param \rsCore\File $File
	 * @return array Array von PhotoAlbum-Instanzen
	 * @api
	 */
	public static function getAlbumsByFile( \rsCore\File $File ) {
		$albums = array();
		foreach( self::getRelationsByFile( $File ) as $Relation )
			$albums[] = $Relation->getAlbum();
		return $albums;
	}


	/** Gibt alle Fotos eines Albums zurück
	 * @param PhotoAlbum $Album
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public static function getFilesByAlbum( PhotoAlbum $Album ) {
		$files = array();
		foreach( self::getRelationsByAlbum( $Album ) as $Relation )
			$files[] = $Relation->getFile();
		return $files;
	}


	/** Gibt alle Fotos eines Albums zurück
	 * @param PhotoAlbum $Album
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

	/** Gibt das Album zurück
	 * @return PhotoAlbum
	 * @api
	 */
	public function getAlbum() {
		return PhotoAlbum::getByPrimaryKey( $this->albumId );
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