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
interface PluginsInterface extends PluginInterface {
}


/** SettingsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Plugins extends Plugin implements PluginsInterface {


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
		$Framework->registerHook( $Plugin, 'save_activated', 'api_saveActivated' );
	}


	/** Gibt den Titel des Settings-Plugins zurück
	 * @return string
	 */
	public static function getSettingsTitle() {
		return self::t("Plugins");
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
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
	#	$Container = \Templates\Base::buildCollapsibleSection( $Container, self::t("Active plugins"), true );

		$Container = $Container->subordinate( 'form.autobind-api', array('action' => 'save_activated', 'method' => 'post') );

		$Table = $Container->subordinate( 'table#pluginsTable.table table-hover table-striped' );
		$TableBody = $Table->subordinate( 'tbody' );

		$ActivatedPluginsSetting = self::getPluginsSetting();
		$activatedPlugins = $ActivatedPluginsSetting ? json_decode( $ActivatedPluginsSetting->value ) : array();
		if( !is_array( $activatedPlugins ) )
			$activatedPlugins = array();

		foreach( \Autoload::getPlugins( false ) as $pluginName ) {
			$active = in_array( $pluginName, $activatedPlugins );
			$attr = array('name' => 'active_plugins[]', 'value' => $pluginName);
			if( $active )
				$attr['checked'] = 'true';
			$Row = $TableBody->subordinate( 'tr' );
			$Row->subordinate( 'td.col-md-1' )->subordinate( 'input(checkbox)', $attr );
			$Row->subordinate( 'td.col-md-11', @array_pop( explode( '\\', $pluginName ) ) );
		}

		$Container->subordinate( 'button(submit).btn btn-primary', self::t("Save") );
	}


/* API Plugin */

	/** Speichert die Nutzerdaten
	 * @return array
	 */
	public function api_saveActivated( $params ) {
		$selectedPlugins = array();
		foreach( postVar( 'active_plugins', array() ) as $pluginName ) {
			$selectedPlugins[] = addslashes( $pluginName );
		}

		$ActivatedPluginsSetting = self::getPluginsSetting();
		if( $ActivatedPluginsSetting ) {
			$ActivatedPluginsSetting->value = json_encode( $selectedPlugins );
			if( $ActivatedPluginsSetting->adopt() )
				return true;
			else
				return $ActivatedPluginsSetting;
		}
		return false;
	}


/* Private methods */

	/** Gibt den Setting-Datensatz zurück, der die Liste aktivierter Plugins enthält
	 * @return \Brainstage\Setting
	 */
	protected static function getPluginsSetting() {
		return \Brainstage\Setting::getTextSetting( 'Brainstage/Plugins' );
	}


}