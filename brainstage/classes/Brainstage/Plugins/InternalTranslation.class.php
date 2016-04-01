<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Plugins;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface InternalTranslationInterface extends PluginInterface {
}


/** TranslationPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class InternalTranslation extends Translation implements InternalTranslationInterface {


	const DEFAULT_INTERVAL_SIZE = 20;
	const DEFAULT_BASE_LANGUAGE = 'en';


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function brainstageRegistration( \rsCore\FrameworkInterface $Framework ) {
		if( self::getUserRight( 'use' ) ) {
			$Plugin = self::instance();
			$Framework->registerHook( $Plugin, 'buildHead' );
			$Framework->registerHook( $Plugin, 'buildBody' );
			$Framework->registerHook( $Plugin, 'getNavigatorItem' );
		}
	}


	/** Wird von Brainstage aufgerufen, damit sich das Plugin in die Menüreihenfolge einsortieren kann
	 * @return int Desto höher der Wert, desto weiter oben erscheint das Plugin
	 */
	public static function brainstageSortValue() {
		return 69;
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'list', 'api_getTranslations' );
		$Framework->registerHook( $Plugin, 'list_keys', 'api_getTranslationKeys' );
		$Framework->registerHook( $Plugin, 'machine_translation', 'api_getMachineTranslations' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveTranslations' );
		$Framework->registerHook( $Plugin, 'clean', 'api_deleteAllTranslations' );
		$Framework->registerHook( $Plugin, 'remove', 'api_deleteTranslations' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		$privileges = parent::registerPrivileges();
		if( is_string( $privileges ) )
			$privileges = explode( ',', $privileges );
		$privileges[] = 'use';
		return $privileges;
	}


/* Private Methoden */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


	protected function getPluginTitle() {
		return self::t("Translation (Brainstage)");
	}


	protected function getTranslations( $languageCode, $start=0, $limit=self::DEFAULT_INTERVAL_SIZE ) {
		return \Brainstage\InternalDictionaryTranslation::getTranslationsByLanguage( $languageCode, $limit, $start );
	}


	protected function getListIntervalSize() {
		return self::DEFAULT_INTERVAL_SIZE;
	}


	protected function getPaginationMax() {
		return ceil( \Brainstage\InternalDictionaryTranslation::countTranslationKeys() / $this->getListIntervalSize() );
	}


/* Brainstage Plugin */

	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript( 'static/js/internal-translation.js' );
		$Head->linkStylesheet( 'static/css/translation.css' );
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return $this->getPluginTitle();
	}


	/** Baut den Sprach-Selektor
	 * @param \rsCore\Container $Container
	 */
	public function buildLanguageSelector( \rsCore\Container $Container ) {
		$usersLanguages = array_keys( \rsCore\Useragent::detectLanguages() );
		$preselectedLanguage = \rsCore\Localization::extractLanguageCode( current( $usersLanguages ) );
		$languagesAllowedToEdit = $this->getLanguagesAllowedToEdit();

		if( count( $languagesAllowedToEdit ) > 1 ) {
			$LanguageSelector = $Container->subordinate( 'select.selectize:language' );
			foreach( $languagesAllowedToEdit as $Language ) {
				$attr = array('value' => $Language->shortCode);
				if( $Language->shortCode == $preselectedLanguage )
					$attr['selected'] = 'selected';
				$LanguageSelector->subordinate( 'option', $attr, $Language->name );
			}
		}
		else {
			$Language = current( $languagesAllowedToEdit );
			$Container->swallow( $Language->name );
			$Container->subordinate( 'input(hidden):language='. $Language->shortCode );
		}
	}


/* API Plugin */

	/** Gibt ein Array von Translation-Einträgen zurück
	 * @return array
	 */
	public function api_getTranslations( $params ) {
		self::throwExceptionIfNotAuthorized();
		$languageCode = valueByKey( $params, 'language' );
		$start = valueByKey( $params, 'start', 0 );
		$limit = valueByKey( $params, 'limit', self::DEFAULT_INTERVAL_SIZE );
		$array = array();

		// Ergänze vorhandene Übersetzungen um möglicherweise bisher nicht in der Sprache vorliegende Schlüssel
		foreach( \Brainstage\InternalDictionaryTranslation::getTranslationKeysNotDefinedInLanguage( $languageCode ) as $Translation ) {
			\Brainstage\InternalDictionaryTranslation::addTranslation( $languageCode, $Translation->key, null );
		}

		// Hole alle vorhandenen Übersetzungen
		foreach( $this->getTranslations( $languageCode, $start*$limit, $limit ) as $Translation ) {
			$columns = $Translation->getColumns();
			unset( $columns['language'] );
			$array[ $Translation->key ] = $columns;
		}

		ksort( $array );
		return array_values( $array );
	}


	/** Gibt ein Array der existierenden Übersetzungsschlüssel zurück
	 * @return array
	 */
	public function api_getTranslationKeys( $params ) {
		self::throwExceptionIfNotAuthorized();
		$array = array();
		foreach( \Brainstage\InternalDictionaryTranslation::getTranslationKeys() as $Translation ) {
			$columns = $Translation->getColumns();
			unset( $columns['language'] );
			$array[ $Translation->key ] = $columns;
		}
		return $array;
	}


	/** Ermittelt eine maschinelle Übersetzung
	 * @return mixed
	 */
	public function api_getMachineTranslations( $params ) {
		self::throwExceptionIfNotAuthorized();
		$languageCode = getVar( 'language' );
		$translationKeys = getVar( 'keys', array( getVar( 'key' ) ) );

		$translations = array();
		foreach( $translationKeys as $key ) {
			if( $key !== null ) {
				$translations[ $key ] = $this->getGoogleTranslation( $key, $languageCode );
			}
		}

		return $translations;
	}


	/** Speichert die übermittelten Übersetzungen
	 * @return boolean
	 */
	public function api_saveTranslations( $params ) {
		self::throwExceptionIfNotPrivileged( 'edit' );
		$languageCode = postVar( 'language' );
		$translations = postVar( 'translations' );

		foreach( $translations as $translationPair ) {
			$Translation = \Brainstage\InternalDictionaryTranslation::getByPrimaryKey( intval( $translationPair['id'] ) );
			if( $Translation->language == $languageCode ) {
				$Translation->translation = $translationPair['translation'];
				$Translation->adopt();
			}
		}

		return true;
	}


	/** Löscht alle protokollierten Translation
	 * @return boolean
	 */
	public function api_deleteAllTranslations( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete,deleteAll' );
		$success = true;
		foreach( \Brainstage\InternalDictionaryTranslation::getTranslations() as $Translation ) {
			if( !$Translation->remove() )
				$success = false;
		}
		return $success;
	}


	/** Löscht selektierte Translation
	 * @return boolean
	 */
	public function api_deleteTranslations( $params ) {
		self::throwExceptionIfNotPrivileged( 'delete' );
		$success = true;
		foreach( $_GET['ids'] as $id ) {
			$Translation = \Brainstage\InternalDictionaryTranslation::getById( intval( $id ) );
			if( !$Translation->remove() )
				$success = false;
		}
		return $success;
	}


}