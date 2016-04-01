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
interface PhotoAlbumMetaInterface {

	public static function addMeta( PhotoAlbum $Album, $languageCodeOrInstance );

	public static function getMetaByAlbum( PhotoAlbum $Album, $languageCodeOrInstance );

	public function getAlbum();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class PhotoAlbumMeta extends \rsCore\DatabaseDatasetAbstract implements PhotoAlbumMetaInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'photo-album-meta';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return PhotoMeta
	 * @api
	 */
	public static function addMeta( PhotoAlbum $Album, $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$Meta = self::getMetaByAlbum( $Album, $Language, false );
		if( $Meta )
			return $Meta;
		$Meta = self::create();
		if( $Meta ) {
			$Meta->albumId = $Album->getPrimaryKeyValue();
			$Meta->language = $Language->shortCode;
			$Meta->adopt();
		}
		return $Meta;
	}


	/** Gibt alle PhotoMetas mit einem bestimmten Titel zurück
	 * @param string $title
	 * @return array Array von PhotoMeta-Instanzen
	 * @api
	 */
	public static function getMetaByAlbum( PhotoAlbum $Album, $languageCodeOrInstance=null, $createIfDoesNotExist=true ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$Meta = self::getByColumns( array('albumId' => $Album->getPrimaryKeyValue(), 'language' => $Language->shortCode) );
		if( !$Meta && $createIfDoesNotExist )
			$Meta = self::addMeta( $Album, $Language );
		return $Meta;
	}


/* Public methods */

	/** Gibt das zu diesem Datensatz gehörende Album zurück
	 * @return PhotoAlbum
	 * @api
	 */
	public function getAlbum() {
		return PhotoAlbum::getAlbumById( $this->albumId );
	}


/* Private methods */

	protected static function getLanguageInstance( $languageCodeOrInstance ) {
		if( $languageCodeOrInstance === null )
			$languageCodeOrInstance = \rsCore\Localization::getLanguage();
		if( $languageCodeOrInstance instanceof \Brainstage\Language )
			return $languageCodeOrInstance;
		elseif( is_string( $languageCodeOrInstance ) )
			return \Brainstage\Language::getLanguageByShortCode( $languageCodeOrInstance );
		return null;
	}


	protected function encodeTitle( $value ) {
		return strip_tags( $value );
	}


	protected function decodeTitle( $value ) {
		return strip_tags( $value );
	}


	protected function encodeDescription( $value ) {
		return strip_tags( $value );
	}


	protected function decodeDescription( $value ) {
		return strip_tags( $value );
	}


}