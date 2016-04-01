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
interface ExceptionsInterface extends PluginInterface {
}


/** ExceptionsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Exceptions extends \Brainstage\Plugin implements ExceptionsInterface {


	const DEFAULT_EXCEPTION_LIMIT = 20;


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
		return 60;
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'list', 'api_getExceptions' );
		$Framework->registerHook( $Plugin, 'clean', 'api_deleteAllExceptions' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'delete,deleteAll';
	}


/* Brainstage Plugin */

	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript( 'static/js/exceptions.js' );
	}


	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Exceptions");
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'header' );
		$Toolbar->subordinate( 'button.btn btn-default refreshExceptions btn-primary', self::t("Refresh") );
		if( self::may('deleteAll') )
			$Toolbar->subordinate( 'button.btn btn-default cleanExceptions', self::t("Remove all") );
		$this->buildExceptionList( $Container );
		$this->buildPagination( $Container );
	}


	/** Baut die Exception-Liste
	 * @param \rsCore\Container $Container
	 */
	public function buildExceptionList( \rsCore\Container $Container ) {
		$Table = $Container->subordinate( 'div.headered > table#exceptionList.table table-hover table-striped' );
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', self::t("Date") );
		$Row->subordinate( 'th', self::t("File (Line)") );
		$Row->subordinate( 'th', self::t("Title") );
		$TableBody = $Table->subordinate( 'tbody' );
/*
		foreach( $this->getExceptions() as $Exception ) {
			$occurrenceString = $Exception->getFile() .' ('. $Exception->getLine() .')';

			$fileTooltipAttr = array(
				'data-toggle' => 'tooltip',
				'data-trigger' => 'hover',
				'data-placement' => 'auto bottom',
				'data-container' => 'body',
				'data-title' => $Exception->getFile(),
			#	'data-content' => $Exception->getText()
			);

			$detailsModalAttr = array(
				'data-toggle' => 'modal',
				'data-trigger' => 'click',
				'data-target' => '#exceptionModal'. $Exception->id
			);

			$Modal = $ModalSpace->subordinate( 'div.modal fade#exceptionModal'. $Exception->id .' > div.modal-dialog > div. modal-content' );
			$Modal->subordinate( 'div.modal-header' )
				->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
					->subordinate( 'span', array('aria-hidden' => 'true'), '&times;' )
					->parent()
				->parent()
				->subordinate( 'h4.modal-title', $occurrenceString );
			$ModalBody = $Modal->subordinate( 'div.modal-body' );
			$ModalBody->subordinate( 'b', $Exception->getTitle() );
			if( $Exception->getTitle() !== $Exception->getText() )
				$ModalBody->subordinate( 'div', $Exception->getText() );
			$sourceFrame = 5;
			$ModalBody->subordinate( 'div.file-source.source-reader', array(
				'data-line' => $sourceFrame ? $sourceFrame+1 : $Exception->getLine(),
				'data-read-only' => 'true',
				'data-syntax-mode' => 'php'
			), htmlentities( self::getFileSource( $Exception, $sourceFrame ) ) );

			$Row = $TableBody->subordinate( 'tr' );
			$Row->subordinate( 'td', $Exception->getDate( 'd.m.Y | H:i:s' ) );
			$Row->subordinate( 'td > a', $detailsModalAttr, $occurrenceString );
			$Row->subordinate( 'td', $Exception->title );
		}
*/
	}


	/** Baut die Pagination
	 * @param \rsCore\Container $Container
	 */
	public function buildPagination( \rsCore\Container $Container ) {
		$Pagination = $Container->subordinate( 'div.exceptionPagination > ul.pagination' );
	#	$numPages = $this->getPaginationMax();
	#	for( $index = 1; $index <= $numPages; $index++ ) {
	#		$Pagination->subordinate( 'li'. ($this->getPaginationIndex() == $index ? '.active' : '') .' > a', $index );
	#	}
	}


/* Private methods */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


	protected function getExceptions( $start=0, $count=self::DEFAULT_EXCEPTION_LIMIT ) {
		return \Brainstage\ExceptionLog::getExceptions( $count, $start );
	}


	protected static function getFileSource( \Brainstage\ExceptionLog $Exception, $frameSize=null ) {
		$source = file_get_contents( $Exception->getFile(false) );
		if( $frameSize !== null ) {
			$sourceFrame = array();
			if( strpos( $Exception->getFile(), '.php' ) !== false && $Exception->getLine() > $frameSize ) {
		#		$sourceFrame[] = '<'. '?php';
		#		$sourceFrame[] = '...';
			}
			foreach( explode( "\n", $source ) as $i => $line ) {
				$i++;
				if( $i >= $Exception->getLine()-$frameSize && $i <= $Exception->getLine()+$frameSize ) {
		#			if( $i == $Exception->getLine() )
		#				$line = '>>  '. $line;
					$sourceFrame[] = $line;
				}
			}
			if( strpos( $Exception->getFile(), '.php' ) !== false ) {
		#		$sourceFrame[] = '...';
			}
		}
		return $frameSize === null ? $source : implode( "\n", $sourceFrame );
	}


	protected function getListIntervalSize() {
		return getVar( 'limit', self::DEFAULT_EXCEPTION_LIMIT );
	}


	protected function getPaginationMax() {
		return ceil( \Brainstage\ExceptionLog::totalCount() / $this->getListIntervalSize() );
	}


	protected function getPaginationIndex() {
		return getVar( 'page', 1 );
	}


/* API Plugin */

	/** Gibt ein Array von Exception-Einträgen zurück
	 * @return array
	 */
	public function api_getExceptions( $params ) {
		$start = valueByKey( $params, 'start', 0 );
		$limit = $this->getListIntervalSize();
		$array = array();
		foreach( $this->getExceptions( $start*$limit, $limit ) as $Exception ) {
			$columns = $Exception->getColumns();
			$columns['shortFilePath'] = $Exception->getFile();
			$columns['readableDate'] = $Exception->getDate( self::t('Y-m-d H:i:s', 'Date and Time: full year, hours, minutes and seconds') );
			$array[] = $columns;
		}
		return array(
			'list' => $array,
			'pages' => $this->getPaginationMax(),
			'total' => \Brainstage\ExceptionLog::totalCount()
		);
	}


	/** Löscht alle protokollierten Exceptions
	 * @return boolean
	 */
	public function api_deleteAllExceptions( $params ) {
		self::throwExceptionIfNotPrivileged( 'deleteAll' );
		\Brainstage\ExceptionLog::getDatabaseConnection()->truncate();
		return true;
	}


}