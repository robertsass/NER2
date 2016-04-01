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
interface FileManagerInterface {

	static function instance();

	function handleUploads();
	function handleDownload();

	function getUploadsDirectory();

	function getUsersFilesByColumns( array $columns, $allowMultipleResults );
	function getFileById( $fileId );
	function getFilesByUser( $userInstanceOrId );
	function getFilesByTag( $tagInstanceOrId );
	function getFilesByName( $fileName );
	function getFilesByType( $fileType );
	function getFilesByDate( $uploadDate );
	function getAllFiles( $limit, $start, $sorting );

	function countUsersFiles();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class FileManager extends CoreClass implements FileManagerInterface {


	const DEFAULT_UPLOADS_DIR = 'uploads/';

	private static $_singleton;

	private $_user;
	private $_uploadsDir;


/* Static methods */

	/** Gibt die statische Singleton-Instanz zurück
	 * @return FileManager
	 * @api
	 */
	public static function instance() {
		if( self::$_singleton === null )
			self::$_singleton = new self();
		return self::$_singleton;
	}


/* Public methods */

	public function __construct( $uploadsDirectory=null, $considerUsersRights=false ) {
		$siteDir = \rsCore\Core::getSiteDirectory( true );
		if( $uploadsDirectory === null )
			$this->_uploadsDir = self::DEFAULT_UPLOADS_DIR;
		else
			$this->_uploadsDir = $uploadsDirectory;

		if( $considerUsersRights ) {
			if( is_object( $considerUsersRights ) )
				$this->_user = $considerUsersRights;
			elseif( \rsCore\Auth::isLoggedin() )
				$this->_user = \rsCore\Auth::getUser();
		}
	}


	/** Behandelt Datei-Uploads und gibt die repräsentativen File-Instanzen zurück
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public function handleUploads() {
		$uploadedFiles = array();
		foreach( $_FILES as $file ) {
			if( is_array( $file['name'] ) ) {
				$files = array();
				$columns = array_keys( $file );
				foreach( $file['name'] as $i => $name ) {
					foreach( $columns as $column )
						$files[ $i ][ $column ] = $file[ $column ][ $i ];
				}
				foreach( $files as $file )
					$uploadedFiles[] = $this->uploadFile( $file );
			}
			else {
				$uploadedFile = $this->uploadFile( $file );
			}
			if( $uploadedFile )
				$uploadedFiles[] = $uploadedFile;
		}
		return $uploadedFiles;
	}


	/** Reagiert auf eine Datei-Anfrage
	 * @api
	 * @todo Rechte berücksichtigen
	 */
	public function handleDownload() {
		$fileId = getVar('f');
		if( $fileId ) {
			$File = self::getFileById( $fileId );
			$siteDir = \rsCore\Core::getSiteDirectory( true );
			$filePath = ( $siteDir ? $siteDir : dirname( BASE_SCRIPT_FILE ) ) .'/'. $File->path;

			if( md5_file( $filePath ) != $File->md5 || sha1_file( $filePath ) != $File->sha1 ) {
				die( "File seems to be corrupt." );
			}

			$size = getVar('size');
			if( $size && $File->isImage() ) {
				$dimensions = explode( 'x', $size, 2 );
				if( count( $dimensions ) == 1 )
					$dimensions[1] = $dimensions[0];
				$maxWidth = intval( $dimensions[0] );
				$maxHeight = intval( $dimensions[1] );
				if( $maxWidth > 0 && $maxHeight > 0 ) {
					$cacheDirectory = dirname( $filePath ) .'/'. $maxWidth .'x'. $maxHeight;
					$this->ensureDirectoryExistance( $cacheDirectory );
					$thumbnailPath = $cacheDirectory .'/'. basename( $filePath );
					if( !is_file( $thumbnailPath ) ) {
						copy( $filePath, $thumbnailPath );
						if( $File->getFileType() == 'png' ) {
							Graphics::resizePNG( $thumbnailPath, $maxWidth, $maxHeight );
							$filePath = $thumbnailPath;
						}
						elseif( $File->getFileType() == 'jpeg' ) {
							Graphics::resizeJPEG( $thumbnailPath, $maxWidth, $maxHeight );
							$filePath = $thumbnailPath;
						}
					} else {
						$filePath = $thumbnailPath;
					}
				}
			}

			$disposition = getVar('download') !== null ? 'attachment' : '';

			@ob_end_clean();

			header( 'Content-Type: '. $File->type );
		#	header( 'Content-Length: '. $File->size );
			header( 'Content-Disposition: '. $disposition .'; filename="'. $File->filename .'"' );
		#	header( 'X-Sendfile: '. realpath( $filePath ) );
			readfile( $filePath );
			exit;
		}
	}


	/** Gibt das Upload-Verzeichnis zurück
	 * @return string
	 * @api
	 */
	public function getUploadsDirectory( $returnPath=true ) {
		$dir = $this->_uploadsDir;
		$siteDir = \rsCore\Core::getSiteDirectory( true );
		if( $returnPath )
			$dir = rtrim( $siteDir, '/' ) .'/'. $dir;
		$this->ensureDirectoryExistance( $dir );
		return $dir;
	}


	/** Stellt sicher, dass das Verzeichnis existiert
	 * @param $dir
	 * @return boolean
	 * @api
	 */
	public function ensureDirectoryExistance( $path ) {
		$walkedPath = array();
		foreach( explode( '/', $path ) as $pathSegment ) {
			$walkedPath[] = $pathSegment;
			$curDir = join( '/', $walkedPath );
			if( strlen( $curDir ) > 0 && !is_dir( $curDir ) )
				mkdir( $curDir );
		}
		return is_dir( join( '/', $walkedPath ) );
	}


	/** Gibt Dateien des Nutzers anhand bestimmter Datenfelder zurück
	 * @param $columns
	 * @param $allowMultipleResults
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public function getUsersFilesByColumns( array $columns, $allowMultipleResults=true ) {
		if( $this->getUser() )
			return File::getByColumns( array_merge( $columns, array( 'userId' => $this->getUser() ) ), $allowMultipleResults );
		return File::getByColumns( $columns, $allowMultipleResults );
	}


	/** Gibt eine Datei anhand seiner ID zurück
	 * @param $fileId
	 * @return File
	 * @api
	 */
	public function getFileById( $fileId ) {
		return File::getByPrimaryKey( $fileId );
	}


	/** Gibt Dateien eines Nutzers zurück
	 * @param $userInstanceOrId
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public function getFilesByUser( $userInstanceOrId ) {
		if( is_object( $userInstanceOrId ) )
			$userId = intval( $userInstanceOrId->getPrimaryKeyValue() );
		else
			$userId = intval( $userInstanceOrId );
		return File::getByColumns( array( 'userId' => $userId ), true );
	}


	/** Gibt Dateien anhand eines Tags (ggf. nur die des Nutzers) zurück
	 * @param $tagInstanceOrId
	 * @return array Array von File-Instanzen
	 * @api
	 * @todo Implementieren!
	 */
	public function getFilesByTag( $tagInstanceOrId ) {
		if( is_object( $tagInstanceOrId ) )
			$tagId = intval( $tagInstanceOrId->getPrimaryKeyValue() );
		else
			$tagId = intval( $tagInstanceOrId );
	#	return File::getByColumns( array( 'userId' => $tagId ), true );
	}


	/** Gibt Dateien anhand des Namens (ggf. nur die des Nutzers) zurück
	 * @param $fileName
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public function getFilesByName( $fileName ) {
		return static::getUsersFilesByColumns( array( 'filename' => $fileName ) );
	}


	/** Gibt Dateien anhand des Typs (ggf. nur die des Nutzers) zurück
	 * @param $fileType
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public function getFilesByType( $fileType ) {
		return static::getUsersFilesByColumns( array( 'type' => $fileType ) );
	}


	/** Gibt Dateien anhand des Typs (ggf. nur die des Nutzers) zurück
	 * @param $fileType
	 * @return array Array von File-Instanzen
	 * @api
	 * @todo Implementieren!
	 */
	public function getFilesByDate( $uploadDate ) {
	#	return static::getUsersFilesByColumns( array( 'type' => $fileType ) );
	}


	/** Gibt alle Dateien (ggf. nur die des Nutzers) zurück
	 * @param $limit
	 * @param $start
	 * @param $sorting
	 * @return array Array von File-Instanzen
	 * @api
	 */
	public function getAllFiles( $limit=null, $start=null, $sorting=null ) {
		if( $sorting === null )
			$sorting = array( 'id' => 'DESC' );
		if( $this->getUser() )
			return File::getByColumns( array( 'userId' => $this->getUser()->getPrimaryKeyValue() ), true, $sorting, $limit, $start );
		return File::getByColumns( array(), true, $sorting, $limit, $start );
	}


	/** Zählt alle Dateien des Nutzers
	 * @return integer
	 * @api
	 */
	public function countUsersFiles() {
		if( $this->getUser() )
			return intval( File::count( '`userId` = "'. $this->getUser()->getPrimaryKeyValue() .'"' ) );
		return File::totalCount();
	}


/* Private methods */

	protected function getUser() {
		return $this->_user;
	}


	protected function uploadFile( $uploadedFile ) {
		$name = $uploadedFile['name'];
		$fileType = $uploadedFile['type'];
		$tempPath = $uploadedFile['tmp_name'];

		if( is_uploaded_file( $tempPath ) ) {
			return $this->moveUploadedFile( $name, $tempPath );
		} elseif( is_file( $tempPath ) ) {
			return $this->copyFile( $name, $tempPath );
		}
		return null;
	}


	protected function moveUploadedFile( $name, $tempPath ) {
		$FileInstance = \Brainstage\File::addFile( $name );
		if( $FileInstance ) {
			$filePath = $this->getUploadsDirectory() . $FileInstance->getPrimaryKeyValue();
			if( move_uploaded_file( $tempPath, $filePath ) ) {
				return $this->setupFileInstance( $FileInstance, $name, $filePath );
			} else {
				$FileInstance->remove( true );
			}
		}
		return null;
	}


	protected function copyFile( $name, $originalPath ) {
		$FileInstance = \Brainstage\File::addFile( $name );
		if( $FileInstance ) {
			$filePath = $this->getUploadsDirectory() . $FileInstance->getPrimaryKeyValue();
			if( copy( $originalPath, $filePath ) ) {
				return $this->setupFileInstance( $FileInstance, $name, $filePath );
			} else {
				$FileInstance->remove( true );
			}
		}
		return null;
	}


	protected function setupFileInstance( \Brainstage\File $FileInstance, $name, $filePath ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$fileType = trim( @array_shift( explode( ';', finfo_file( $finfo, $filePath ) ) ) );
		finfo_close($finfo);

		$FileInstance->path = $this->getUploadsDirectory( false ) . $FileInstance->getPrimaryKeyValue();
		$FileInstance->type = $fileType;
		$FileInstance->size = filesize( $filePath );
		if( is_object( $this->_user ) )
			$FileInstance->userId = $this->_user->getPrimaryKeyValue();
		$FileInstance->md5 = md5_file( $filePath );
		$FileInstance->sha1 = sha1_file( $filePath );
		$FileInstance->adopt();
		return $FileInstance;
	}


}