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
interface ClientInterface {

	public static function addClient( $email, $returnExistant=true );

	public static function getClients();
	public static function getClientById( $clientId );
	public static function getClientByEmail( $email );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Client extends \rsCore\DatabaseDatasetAbstract implements ClientInterface, \rsCore\CoreFrameworkInitializable, \rsCore\DatabaseDatasetCachingInterface {


	protected static $_databaseTable = 'clients';


/* CoreFrameworkInitializable methods */

	/** Wird beim Autoloading dieser Klasse aufgerufen, um diese Klasse als DatabaseDatasetHandler zu registrieren
	 * @internal
	 */
	public static function frameworkRegistration() {
		\rsCore\Core::core()->registerDatabaseDatasetHandler( static::getDatabaseConnection(), '\\'. __CLASS__ );
	}


/* Static methods */

	/** Legt einen neuen Kunden an
	 * @param string $email
	 * @return Client
	 * @api
	 */
	public static function addClient( $email, $returnExistant=true ) {
		if( $email ) {
			$Client = self::getClientByEmail( $email );
			if( $Client ) {
				if( $returnExistant )
					return $Client;
				return null;
			}
		}

		$Client = self::create();
		if( $Client ) {
			$Client->email = $email;
			$Client->adopt();
		}
		return $Client;
	}


	/** Gibt alle Kunden zurück
	 * @return array Array von Client-Objekten
	 * @api
	 */
	public static function getClients() {
		return self::getAll();
	}


	/** Gibt einen Kunden anhand seiner ID zurück
	 * @param int $clientId
	 * @return Client
	 * @api
	 */
	public static function getClientById( $clientId ) {
		return self::getByPrimaryKey( $clientId );
	}


	/** Gibt einen Kunden anhand seiner eMail-Adresse zurück
	 * @param string $email
	 * @return Client
	 * @api
	 */
	public static function getClientByEmail( $email ) {
		return self::getByColumn( 'email', $email );
	}


/* Public methods */



}