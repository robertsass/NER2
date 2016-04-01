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
interface BlogArticleVersionInterface {

	public static function addVersion( BlogArticle $Article, $languageCodeOrInstance );

	public static function getVersionByArticle( BlogArticle $Article, $languageCodeOrInstance, $createIfDoesNotExist );

	public function getArticle();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class BlogArticleVersion extends \rsCore\DatabaseDatasetAbstract implements BlogArticleVersionInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'blog-article-versions';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return PhotoVersion
	 * @api
	 */
	public static function addVersion( BlogArticle $Article, $languageCodeOrInstance ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$Version = self::getVersionByArticle( $Article, $Language, false );
		if( $Version )
			return $Version;
		$Version = self::create();
		if( $Version ) {
			$Version->articleId = $Article->getPrimaryKeyValue();
			$Version->language = $Language->shortCode;
			$Version->adopt();
		}
		return $Version;
	}


	/** Gibt alle PhotoVersions mit einem bestimmten Titel zurück
	 * @param string $title
	 * @return array Array von PhotoVersion-Instanzen
	 * @api
	 */
	public static function getVersionByArticle( BlogArticle $Article, $languageCodeOrInstance=null, $createIfDoesNotExist=true ) {
		$Language = self::getLanguageInstance( $languageCodeOrInstance );
		$Version = self::getByColumns( array('articleId' => $Article->getPrimaryKeyValue(), 'language' => $Language->shortCode) );
		if( !$Version && $createIfDoesNotExist )
			$Version = self::addVersion( $Article, $Language );
		return $Version;
	}


/* Public methods */

	/** Gibt das zu diesem Datensatz gehörende Article zurück
	 * @return BlogArticle
	 * @api
	 */
	public function getArticle() {
		return BlogArticle::getArticleById( $this->articleId );
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


	protected function encodeSubtitle( $value ) {
		return strip_tags( $value );
	}


	protected function decodeSubtitle( $value ) {
		return strip_tags( $value );
	}


	protected function encodeTeaser( $value ) {
		return strip_tags( $value );
	}


	protected function decodeTeaser( $value ) {
		return strip_tags( $value );
	}


	protected function encodeText( $value ) {
		return strip_tags( $value );
	}


	protected function decodeText( $value ) {
		return strip_tags( $value );
	}


}