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
interface FeedbackInterface {

	public static function addFeedback( $comment, $author );

	public static function getFeedbackById( $feedbackId );
	public static function getFeedbacks( $limit=null );
	public static function getPublicFeedbacks( $limit=null );

	public function getClient();

	public function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Feedback extends \rsCore\DatabaseDatasetAbstract implements FeedbackInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'feedbacks';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt eine neue Veranstaltung an
	 * @return Feedback
	 * @api
	 */
	public static function addFeedback( $comment, $author ) {
		$Now = new \rsCore\Calendar();
		$Feedback = self::create();
		$Feedback->date = $Now->getDateTime();
		$Feedback->comment = $comment;
		$Feedback->author = $author;
		$Feedback->adopt();
		return $Feedback;
	}


	/** Gibt ein Feedback anhand seiner ID zurück
	 * @param integer $feedbackId
	 * @return Feedback
	 * @api
	 */
	public static function getFeedbackById( $feedbackId ) {
		return self::getByPrimaryKey( $feedbackId );
	}


	/** Gibt alle Feedbacks zurück
	 * @param int $limit
	 * @return array Array von Feedback-Instanzen
	 * @api
	 */
	public static function getFeedbacks( $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '1=1 ORDER BY `date` DESC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


	/** Gibt alle öffentlich lesbaren Feedbacks zurück
	 * @param int $limit
	 * @return array Array von Feedback-Instanzen
	 * @api
	 */
	public static function getPublicFeedbacks( $limit=null ) {
		$DatabaseConnector = self::getDatabaseConnection();
		$condition = '`public` = "1"';
		$condition .= ' ORDER BY `date` DESC';
		if( $limit !== null )
			$condition .= ' LIMIT 0,'. intval( $limit );
		return self::getAll( $condition );
	}


/* Public methods */

	/** Gibt den Client zurück, in dessen Namen das Feedback stattfindet
	 * @return Client
	 * @api
	 */
	public function getClient() {
		return Client::getById( $this->clientId );
	}


/* Private methods */

	protected function encodeDate( $value ) {
		return \rsCore\DatabaseConnector::encodeDatetime( $value );
	}


	protected function decodeDate( $value ) {
		return \rsCore\Calendar::parseDateTime( \rsCore\DatabaseConnector::decodeDatetime( $value ) );
	}


	protected function encodeAuthor( $value ) {
		return \rsCore\StringUtils::getPlainText( $value );
	}


	protected function encodeComment( $value ) {
		return \rsCore\StringUtils::getPlainText( $value );
	}


}