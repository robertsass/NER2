<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Plugins\Dashboard;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface DiskCapacityInterface extends PluginInterface {
}


/** SettingsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class DiskCapacity extends Plugin implements DiskCapacityInterface {


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function dashboardRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'buildWidget' );
	}


	/** Gibt den Titel des Dashboard-Widgets zurück
	 * @return string
	 */
	public static function getDashboardWidgetTitle() {
		return self::t("Capacity");
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


/* Brainstage Plugin */

	/** Baut das Widget
	 * @param \rsCore\Container $Container
	 */
	public function buildWidget( \rsCore\Container $Container ) {
		$this->buildDiskCapacityDiagram( $Container );
	}


	/** Baut das Formular zum Anlegen neuer Sprachen
	 * @param \rsCore\Container $Container
	 */
	public function buildDiskCapacityDiagram( \rsCore\Container $Container ) {
		$capacityLabel = self::getLabel( '{capacity} | {free} '. self::t("free") );
		$percentage = self::getLabel( "{percentage}" );
		$percentageLabel = $percentage .' %';
		$usageLabel = self::getLabel( '{usage} '. self::t("occupied") );

		$progressAttr = array('data-toggle' => 'tooltip', 'data-placement' => 'top', 'title' => $capacityLabel);
		$progressbarAttr = array('style' => 'width: '. $percentage .'%', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => $usageLabel);

		$Progressbar = $Container->subordinate( 'div.progress', $progressAttr )
			->subordinate( 'div.progress-bar', $progressbarAttr, $percentageLabel );
	}
	
	
	/** Baut die Beschriftung zusammen
	 * @param string $label
	 * @return string
	 */
	protected static function getLabel( $label ) {
		$capacity = disk_total_space('.');
		$freeCapacity = disk_free_space('.');
		$usedCapacity = $capacity - $freeCapacity;
		$percentage = round( $usedCapacity / $capacity, 2 ) *100;
		$label = str_replace( '{usage}', \rsCore\Core::functions()->readableFileSize( $usedCapacity ), $label );
		$label = str_replace( '{capacity}', \rsCore\Core::functions()->readableFileSize( $capacity ), $label );
		$label = str_replace( '{free}', \rsCore\Core::functions()->readableFileSize( $freeCapacity ), $label );
		$label = str_replace( '{percentage}', $percentage, $label );
		return $label;
	}


}