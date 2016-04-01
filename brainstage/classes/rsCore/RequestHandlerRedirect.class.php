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
interface RequestHandlerRedirectInterface {

	static function addRedirectUrl( $redirectUrl );
	static function getRedirectByUrl( $redirectUrl );

	function __construct();
	function getRedirectUrl();
	function redirect();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class RequestHandlerRedirect extends DatabaseDatasetAbstract implements RequestHandlerRedirectInterface {


	const ERROR_CANT_DUPLICATE = "Can't duplicate rule.";


	protected static $_databaseTable = 'brainstage-url-redirects';


	/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


	/* Static methods */

	public static function addRedirectUrl( $redirectUrl ) {
		$Redirect = self::getRedirectByUrl( $redirectUrl );
		if( !$Redirect ) {
			$Redirect = self::create();
			if( $Redirect ) {
				$Redirect->redirectUrl = $redirectUrl;
				$Redirect->adopt();
			}
		}
		return $Redirect;
	}


	public static function getRedirectByUrl( $redirectUrl ) {
		return self::getByColumn( 'redirectUrl', $redirectUrl );
	}


	/* Public methods */

	public function __construct() {
	}


	public function duplicate() {
		throw new Exception( self::ERROR_CANT_DUPLICATE );
	}


	/** Gibt die Ziel-URL zurück
	 * @return string
	 * @api
	 */
	public function getRedirectUrl() {
		return $this->redirectUrl;
	}


	/** Leitet auf die Ziel-URL um
	 * @api
	 */
	public function redirect() {
		Core::functions()->redirect( $this->getRedirectUrl() );
	}


}