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
interface ReviewInterface {

	static function add( CrawlerMovie $Movie, CrawlerReview $Review );

	static function getByMovie( CrawlerMovie $Movie );
	static function getByReview( CrawlerReview $Review );
	static function getSuitableReviews();

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Review extends \rsCore\DatabaseDatasetAbstract implements ReviewInterface, \rsCore\CoreFrameworkInitializable {


	protected static $_databaseTable = 'reviews';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Findet einen Datensatz anhand des Titels und der URL oder legt ihn an
	 * @param CrawlerMovie $Movie
	 * @param CrawlerReviewSearch $ReviewSearch
	 * @return CrawlerReview
	 * @api
	 */
	public static function add( CrawlerMovie $Movie, CrawlerReview $Review ) {
		$Dataset = self::getByColumns( array(
			'movieId' => $Movie->getPrimaryKeyValue(),
			'crawlerReviewId' => $Review->getPrimaryKeyValue()
		), false );
		if( !$Dataset ) {
			$Dataset = self::create();
			if( $Dataset ) {
				$Dataset->movieId = $Movie->getPrimaryKeyValue();
				$Dataset->crawlerReviewId = $Review->getPrimaryKeyValue();
				$Dataset->adopt();
			}
		}
		return $Dataset;
	}


	/** Findet Datensätze anhand eines Movie
	 * @param CrawlerMovie $Movie
	 * @return array Array von CrawlerReview-Instanzen
	 * @api
	 */
	public static function getByMovie( CrawlerMovie $Movie ) {
		return self::getByColumns( array(
			'movieId' => $Movie->getPrimaryKeyValue()
		), true );
	}


	/** Findet einen Datensatz anhand eines ReviewSearch
	 * @param CrawlerReviewSearch $ReviewSearch
	 * @return CrawlerReview
	 * @api
	 */
	public static function getByReview( CrawlerReview $Review ) {
		return self::getByColumns( array(
			'crawlerReviewId' => $Review->getPrimaryKeyValue()
		), false );
	}


	/** Findet nur die geeigneten Datensätze
	 * @param CrawlerMovie $Movie
	 * @return array Array von Review-Instanzen
	 * @api
	 */
	public static function getSuitableReviews() {
		static::getDatabaseConnection()->getConnection()->set_charset( 'utf8mb4' );
		return self::getByColumns( array(
			'suitable' => 1
		), true );
	}


/* Filter */

	protected function encodeTitle( $value ) {
		return trim( html_entity_decode( strip_tags( $value ) ) );
	}


	protected function encodeSubtitle( $value ) {
		return trim( html_entity_decode( strip_tags( $value ) ) );
	}


}
