<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace rsCore;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface FileInterface {

	static function addFile( $name );

	static function getFileByName( $name );

	function getUser();
	function getMediaType();
	function getFileType();
	function getFileContents();
	function getURL( $forceDownload, $imageDimensions );

	function isAudio();
	function isImage();
	function isVideo();
	function isPDF();

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class File extends \rsCore\DatabaseDatasetAbstract implements FileInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'brainstage-files';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine Datei-Referenz an
	 * @param string $name
	 * @return File
	 * @api
	 */
	public static function addFile( $name ) {
		$DatabaseConnector = static::getDatabaseConnection();
		$uploadDate = $DatabaseConnector::encodeDatetime( new \DateTime() );

		$File = static::create();
		if( $File ) {
			$File->filename = trim( $name );
			$File->uploadDate = $uploadDate;
			$File->adopt();
		}
		return $File;
	}


	/** Findet eine Datei beim Namen
	 * @param string $name
	 * @return File
	 * @api
	 */
	public static function getFileByName( $name ) {
		return static::getByColumns( array('filename' => trim( $name ) ) );
	}


	/** Gibt den Eigentümer zurück
	 * @return \Brainstage\User
	 * @api
	 */
	public function getUser() {
		return \Brainstage\User::getUserById( $this->userId );
	}


	/** Gibt den Medientyp zurück
	 * @return string
	 * @api
	 */
	public function getMediaType() {
		return current( explode( '/', $this->type ) );
	}


	/** Gibt den Medientyp zurück
	 * @return string
	 * @api
	 */
	public function getFileType() {
		return @array_pop( explode( '/', $this->type ) );
	}


	/** Gibt den Dateiinhalt zurück
	 * @return string
	 * @api
	 */
	public function getFileContents() {
		$siteDir = \rsCore\Core::getSiteDirectory( true );
		$filePath = ( $siteDir ? $siteDir : dirname( BASE_SCRIPT_FILE ) ) .'/'. $this->path;
		return file_get_contents( $filePath );
	}


	/** Gibt die Request-URL für diese Datei zurück
	 * @return string
	 * @api
	 */
	public function getURL( $forceDownload=false, $imageDimensions=null ) {
		$url = './?f='. $this->getPrimaryKeyValue();
		if( $forceDownload )
			$url .= '&download';
		if( $this->isImage() && $imageDimensions !== null )
			$url .= '&size='. urlencode( $imageDimensions );
		return $url;
	}


	/** Prüft ob der Medientyp Audio ist
	 * @return boolean
	 * @api
	 */
	public function isAudio() {
		return $this->getMediaType() == 'audio';
	}


	/** Prüft ob der Medientyp Audio ist
	 * @return boolean
	 * @api
	 */
	public function isImage() {
		return $this->getMediaType() == 'image';
	}


	/** Prüft ob der Medientyp Audio ist
	 * @return boolean
	 * @api
	 */
	public function isVideo() {
		return $this->getMediaType() == 'video';
	}


	/** Prüft ob der Medientyp Audio ist
	 * @return boolean
	 * @api
	 */
	public function isPDF() {
		return $this->getFileType() == 'pdf';
	}


	/** Löscht die Datei aus dem Dateisystem und dessen Eintrag in der Datenbank
	 * @param $forceDatasetRemoval
	 * @return boolean
	 * @api
	 */
	public function remove( $forceDatasetRemoval=false ) {
		$filePath = $this->path;
		if( ( unlink( $filePath ) ) || $forceDatasetRemoval )
			return parent::remove();
		return false;
	}


/* Private methods */

	protected function onChange() {
		parent::onChange();

		// extract and set file suffix
		$explodedFileName = explode( '.', $this->filename );
		$this->suffix = array_pop( $explodedFileName );
	}


	protected function decodeUploadDate( $value ) {
		return \rsCore\DatabaseConnector::decodeDatetime( $value );
	}


	protected function encodeUploadDate( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


}