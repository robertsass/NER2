<?php
namespace Plugins;


interface TextExporterInterface {
}


class TextExporter extends \rsCore\Plugin implements TextExporterInterface {


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


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();

		$Framework->registerHook( $Plugin, 'export', 'api_export' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'export';
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
		parent::init();
	}


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return t("Exporter");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/textexporter.css' );
		$Head->linkScript( '/static/js/textexporter.js' );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => 'export') );
		$Form->subordinate( 'p > label' )
			->subordinate( 'input(checkbox):markupTags' )->append( t("Include markup tags") );
		$Form->subordinate( 'p > label' )
			->subordinate( 'input(checkbox):wrapSentences', array('checked' => 'true') )->append( t("Wrap each sentence in S-tag") );

		$Output = $Container->subordinate( 'div.row > div.col-xs-12 > textarea.form-control:output', array('rows' => 40) );
	}


/* API Plugin */

	/** Gibt den Export-Source zurück
	 * @return boolean
	 */
	public function api_export( $params ) {
		self::throwExceptionIfNotPrivileged( 'export' );
		
		$includeMarkup = $params['includeMarkup'] == 'true' ? true : false;
		$wrapSentences = $params['wrapSentences'] == 'true' ? true : false;
		
		$results = array();
		foreach( \Site\Review::getSuitableReviews() as $Review ) {
			$sentences = explode( '/SENTENCE_END/', preg_replace( '#([^0-9]\.|[\!\?\n\r])#', '${1}/SENTENCE_END/', $Review->text ) );
			foreach( $sentences as $sentence ) {
				$sentence = trim( $sentence );
				if( strpos( $sentence, '<movietitle>' ) !== false ) {
					if( !$includeMarkup )
						$sentence = preg_replace( '#(<\/?movietitle>)#', '', $sentence );
					if( $wrapSentences )
						$sentence = '<S>'. $sentence .'</S>';
					$results[] = $sentence;
				}
			}
		}
		
		$result = implode( "\n", $results );
		return $result;
	}


}