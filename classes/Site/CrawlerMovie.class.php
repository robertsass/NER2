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
interface CrawlerMovieInterface {

	static function add( $title, $url );

	static function getByTitle( $title );
	static function getByUrl( $url );

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CrawlerMovie extends \rsCore\DatabaseDatasetAbstract implements CrawlerMovieInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'crawler-movies';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Findet einen Datensatz anhand des Titels und der URL oder legt ihn an
	 * @param string $title
	 * @param string $url
	 * @return CrawlerMovie
	 * @api
	 */
	public static function add( $title, $url ) {
		$Dataset = self::getByColumns( array('title' => $title, 'url' => $url), false );
		if( !$Dataset ) {
			$Dataset = self::create();
			if( $Dataset ) {
				$Dataset->title = $title;
				$Dataset->url = $url;
				$Dataset->adopt();
			}
		}
		return $Dataset;
	}


	/** Findet einen Datensatz anhand des Titels
	 * @param string $title
	 * @return CrawlerMovie
	 * @api
	 */
	public static function getByTitle( $title ) {
		return self::getByColumns( array('title' => $title), false );
	}


	/** Findet einen Datensatz anhand des Titels
	 * @param string $url
	 * @return CrawlerMovie
	 * @api
	 */
	public static function getByUrl( $url ) {
		return self::getByColumns( array('url' => $url), false );
	}


/* Filter */

	protected function encodeTitle( $value ) {
		return trim( html_entity_decode( strip_tags( $value ) ) );
	}


	protected function encodeUrl( $value ) {
		return strip_tags( $value );
	}


}