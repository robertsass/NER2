<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Plugins;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface BackendInterface {
}


/** BackendPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Backend extends \rsCore\Plugin implements BackendInterface {


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function brainstageRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'extendBrainstageBlog' );
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
#		$Framework->registerHook( $Plugin, 'remove-photo', 'api_removePhoto' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
	#	return 'upload,edit,remove,delete';
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
		parent::init();
	}


/* Brainstage Plugin */

	/** Ergänzt den Brainstage-internen Blog
	 * @param \rsCore\Container $Container
	 */
	public function extendBrainstageBlog( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('method' => 'post') );
		$Form->subordinate( 'h1', "Webmaster-". self::t("Support") );

		if( postVar('message') && postVar('requestToken', null) === sessionVar('webmasterSupport-requestToken', false) ) {
			$message = postVar('message', '');
			$header = 'FROM: '. user()->name .' <'. user()->email .'>';
			$sent = strlen($message) > 5 && mail( 'webmaster@nightfever.org', "Nightfever Webmaster-Support Request", $message, $header );
			if( $sent ) {
				$Form->subordinate( 'div.alert.alert-success', self::t("Your request has been sent. We will reply to you via email.") );
			}
		}

		$token = md5( microtime() . user()->email );
		$_SESSION['webmasterSupport-requestToken'] = $token;

		$Form->subordinate( 'p > input(text).form-control:name', array('readonly' => 'true', 'value' => user()->name) );
		$Form->subordinate( 'p > input(text).form-control:email', array('readonly' => 'true', 'value' => user()->email) );
		$Form->subordinate( 'p > textarea.form-control:message', array('rows' => 8, 'placeholder' => self::t("Please describe your problem as detailed as possible.")) );
		$Form->subordinate( 'input(hidden):requestToken='. $token );
		$Form->subordinate( 'p > input(submit).btn.btn-primary', array('value' => self::t("Send")) );
	}


/* API Plugin */

	/** Fügt ein neues Photo ein
	 * @return boolean
	 */
	public function api_addPhoto( $params ) {
		$City = \Nightfever\City::getCityById( $params['site'] );
		$Location = \Nightfever\Location::getById( $params['location'] );
		$Photo = \Nightfever\Photo::addPhoto( $City );
		if( $Photo ) {
			$Photo->locationId = $Location->getPrimaryKeyValue();
			$Photo->start = \DateTime::createFromFormat( 'd.m.Y H:i', $params['start'] );
			$Photo->end = \DateTime::createFromFormat( 'd.m.Y H:i', $params['end'] );
			$success = $success && $Photo->adopt();
		}
		return $success ? $success : $failures;
	}


}