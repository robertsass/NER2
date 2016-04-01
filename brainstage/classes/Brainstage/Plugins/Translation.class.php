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
interface TranslationInterface extends PluginInterface {
	
	static function getLanguagesAllowedToEdit();
	
}


/** TranslationPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Translation extends \Brainstage\Plugin implements TranslationInterface {


	const DEFAULT_INTERVAL_SIZE = 20;
	const DEFAULT_BASE_LANGUAGE = 'en';


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function brainstageRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'buildHead' );
		$Framework->registerHook( $Plugin, 'buildBody' );
		$Framework->registerHook( $Plugin, 'getNavigatorItem' );
	}


	/** Wird von Brainstage aufgerufen, damit sich das Plugin in die Menüreihenfolge einsortieren kann
	 * @return int Desto höher der Wert, desto weiter oben erscheint das Plugin
	 */
	public static function brainstageSortValue() {
		return 70;
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
		return array(
			'edit' => 'boolean',
			'delete' => 'boolean',
			'deleteAll' => 'boolean',
			'languages' => 'list'
		);
	}


/* Private Methoden */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


	protected function getPluginTitle() {
		return self::t("Translation");
	}


	protected function getTranslations( $languageCode, $start=0, $limit=self::DEFAULT_INTERVAL_SIZE ) {
		return \Brainstage\DictionaryTranslation::getTranslationsByLanguage( $languageCode, $limit, $start );
	}


	protected function getListIntervalSize() {
		return self::DEFAULT_INTERVAL_SIZE;
	}


	protected function getPaginationMax() {
		return ceil( \Brainstage\DictionaryTranslation::countTranslationKeys() / $this->getListIntervalSize() );
	}


	protected function getPaginationIndex() {
		return getVar( 'page', 1 );
	}


	protected function getGoogleTranslation( $key, $targetLanguage, $baseLanguage=self::DEFAULT_BASE_LANGUAGE ) {
		$url = 'https://translate.google.com/translate_a/single?client=t';
		$url .= '&sl='. $baseLanguage;
		$url .= '&tl='. $targetLanguage;
		$url .= '&hl='. $baseLanguage;
		$url .= '&dt=t';
		$url .= '&ie=UTF-8&oe=UTF-8&otf=1&ssel=3&tsel=3&tk=518157|543896';
		$url .= '&q='. urlencode( $key );
		$params = array();
		$Request = \rsCore\Curl::get( $url, $params );
		$response = str_replace( ',,', ',', $Request->getResponse() );
		$translation = json_decode( $response );
		return current( current( current( $translation ) ) );
	}


	public static function getLanguagesAllowedToEdit() {
		$languages = array();
		$languagesAllowedToEdit = self::getUserRight( 'languages' );
		foreach( \Brainstage\Language::getLanguages() as $Language ) {
			if( user()->isSuperAdmin() )
				$languages[] = $Language;
			else {
				foreach( $languagesAllowedToEdit as $Right )
					if( $Right->inList( $Language->shortCode ) ) {
						$languages[] = $Language;
						break;
					}
			}
		}
		return $languages;
	}


/* Brainstage Plugin */

	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript( 'static/js/translation.js' );
		$Head->linkStylesheet( 'static/css/translation.css' );
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return $this->getPluginTitle();
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'colset' );
	#	$Container->subordinate( 'h1', $this->getPluginTitle() );
		$this->buildTranslationTable( $Container );
		$this->buildToolbar( $Container );
	}


	/** Baut die Toolbar
	 * @param \rsCore\Container $Container
	 */
	public function buildToolbar( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'div.row.toolbar' );

		$ActionButtons = $Toolbar->subordinate( 'div.col-md-4' );
		if( self::may('edit') ) {
			$ActionButtons->subordinate( 'button.btn btn-primary saveTranslations', self::t("Save") );
			$ActionButtons->subordinate( 'button.btn btn-default machineTranslations', self::t("Prefill") );
			#$ActionButtons->subordinate( 'button.btn btn-default openTranslationWizard', "Assistent..." );
		}

		$this->buildPagination( $Toolbar->subordinate( 'div.col-md-5' ) );

		$EditingButtons = $Toolbar->subordinate( 'div.col-md-3' );
		if( self::may('edit,delete') ) {
			$EditingButtons->subordinate( 'button.btn btn-default enableDeletion', self::t("Delete...") );
			$DeleteButtons = $EditingButtons->subordinate( 'div.deleteButtons' );
			if( self::may('deleteAll') )
				$DeleteButtons->subordinate( 'button.btn btn-danger cleanTranslationTable', self::t("Clean") );
			$DeleteButtons->subordinate( 'button.btn btn-warning removeSelectedTranslations', self::t("Delete") );
		}
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


	/** Baut die Pagination
	 * @param \rsCore\Container $Container
	 */
	public function buildPagination( \rsCore\Container $Container ) {
		$Pagination = $Container->subordinate( 'ul.pagination' );
		$numPages = $this->getPaginationMax();
		for( $index = 1; $index <= $numPages; $index++ ) {
			$Pagination->subordinate( 'li'. ($this->getPaginationIndex() == $index ? '.active' : '') .' > a', $index );
		}
	}


	/** Baut die Exception-Liste
	 * @param \rsCore\Container $Container
	 */
	public function buildTranslationTable( \rsCore\Container $Container ) {
		$Table = $Container->subordinate( 'table#translationTable.table table-hover table-striped'. (self::may('edit') ? ' editable' : '') );
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', self::t("Translation key") );
		$this->buildLanguageSelector( $Row->subordinate( 'th' ) );
		$TableBody = $Table->subordinate( 'tbody' );
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
		foreach( \Brainstage\DictionaryTranslation::getTranslationKeysNotDefinedInLanguage( $languageCode ) as $Translation ) {
			\Brainstage\DictionaryTranslation::addTranslation( $languageCode, $Translation->key, null, $Translation->comment );
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
		foreach( \Brainstage\DictionaryTranslation::getTranslationKeys() as $Translation ) {
			$array[] = $Translation->key;
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
			$Translation = \Brainstage\DictionaryTranslation::getByPrimaryKey( intval( $translationPair['id'] ) );
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
		foreach( \Brainstage\DictionaryTranslation::getTranslations() as $Translation ) {
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
			$Translation = \Brainstage\DictionaryTranslation::getById( intval( $id ) );
			if( !$Translation->remove() )
				$success = false;
		}
		return $success;
	}


}