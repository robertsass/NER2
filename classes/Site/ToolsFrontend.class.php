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
interface ToolsFrontendInterface {

	static function buildFrontend();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class ToolsFrontend extends Tools implements ToolsFrontendInterface {


/* Static methods */

	/** Baut das Frontend
	 * @api
	 */
	public static function buildFrontend() {
		return new self();
	}


/* Variables */

	private $_Core;


/* Private methods */

	/** Konstruktor
	 * @internal
	 */
	private function __construct() {
		$this->prebuildManipulations();
		$this->coreBuild();
	}


	/** Initialisiert rsCore und startet die Generierung
	 * @internal
	 */
	private function coreBuild() {
		$this->_Core = rsCore();
		$this->_Core->activateErrorHandler();
		$this->_Core->buildPage();
	}


	/** Führt Site-spezifische Manipulationen durch
	 * @internal
	 */
	private function prebuildManipulations() {
		$Request = \rsCore\Core::getRequestPath();
		foreach( $Request->domain->subdomains as $subdomain ) {
			$subdomain = strtolower( $subdomain );
			$Site = \Site\Sites::getSiteByShortname( $subdomain );
			if( $Site ) {
				try {
					$RequestHandler = $Request->getRequestHandler();
					$RequestRule = $RequestHandler->getRule();
					if( $RequestHandler->getTargetType() == \rsCore\RequestHandler::TARGETTYPE_DOCUMENT ) {
						$Document = $RequestHandler->getTarget();
						if( $Document ) {
							$isChildOfSite = false;
							if( $Document->getPrimaryKeyValue() != $Site->documentId ) {
								foreach( $Document->getParents() as $curDocument ) {
									if( $curDocument->getPrimaryKeyValue() == $Site->documentId ) {
										$isChildOfSite = true;
										break;
									}
								}
							}
							else
								$isChildOfSite = true;
							if( !$isChildOfSite ) {
								$Rule = \rsCore\RequestHandler::addRule( $Site->documentId, \rsCore\RequestHandler::TARGETTYPE_DOCUMENT );
								$Pattern = $Rule->addPattern( $Site->shortname, 'exact', 'subdomains' );
		#		var_dump($Rule, $Pattern);
							}
						}
						break;
					}
				} catch( \Exception $Exception ) {
	#				var_dump($Exception);
				}
			}
		}
	}


}