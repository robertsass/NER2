<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Plugins\Settings;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface GeneralInterface extends PluginInterface {
}


/** SettingsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class General extends Plugin implements GeneralInterface {


/* Framework Registrations */

	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function settingsRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'buildHead' );
		$Framework->registerHook( $Plugin, 'buildBody' );
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'save', 'api_save' );
	}


/* General */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


	/** Gibt den Titel des Settings-General zurück
	 * @return string
	 */
	public static function getSettingsTitle() {
		return self::t("General");
	}


	/** Gibt den Setting-Datensatz zurück, der den Sitename beschreibt
	 * @return \Brainstage\Setting
	 */
	protected static function getSitenameSetting() {
		return \Brainstage\Setting::getMixedSetting( 'Brainstage/Sitename' );
	}


/* Brainstage Plugin */

	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
	#	$ModalSpace = $Container->subordinate( 'div.modal-space' );
	#	$Container = \Templates\Base::buildCollapsibleSection( $Container, self::t("Active General"), true );

		$Container = $Container->subordinate( 'form.autobind-api', array('action' => 'save', 'method' => 'post') );

		$Table = $Container->subordinate( 'table#GeneralTable.table table-hover table-striped has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', self::t("Site name") );
		$Row->subordinate( 'td > input(text).form-control:sitename', array('placeholder' => t("Site name"), 'value' => \Brainstage\Brainstage::getSiteName()) );

		$Container->subordinate( 'button(submit).btn btn-primary', self::t("Save") );
	}


/* API Plugin */

	/** Speichert die Nutzerdaten
	 * @return array
	 */
	public function api_save( $params ) {
		$SitenameSetting = self::getSitenameSetting();
		$SitenameSetting->value = postVar('sitename');
		$SitenameSetting->adopt();

		return false;
	}


}