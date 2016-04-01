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
interface PhotosInterface {
}


/** PhotosPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Photos extends \rsCore\Plugin implements PhotosInterface {


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

		$Framework->registerHook( $Plugin, 'new-album', 'api_addAlbum' );
		$Framework->registerHook( $Plugin, 'list-albums', 'api_listAlbums' );
		$Framework->registerHook( $Plugin, 'save-album', 'api_saveAlbum' );
		$Framework->registerHook( $Plugin, 'delete-album', 'api_deleteAlbum' );

		$Framework->registerHook( $Plugin, 'upload', 'api_uploadPhoto' );
		$Framework->registerHook( $Plugin, 'add-photo', 'api_addPhoto' );
		$Framework->registerHook( $Plugin, 'list-photos', 'api_listPhotos' );
		$Framework->registerHook( $Plugin, 'remove-photo', 'api_removePhoto' );
		$Framework->registerHook( $Plugin, 'delete-photo', 'api_deletePhoto' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'upload,addAlbum,editAlbum,deleteAlbum,addPhoto,removePhoto,deletePhoto';
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
		return t("Photos");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/photos.css' );
		$Head->linkScript( '/static/js/photos.js' );
		$Head->addOther( new \rsCore\Container( 'script', 'var localeDateFormat = "'. \Site\Tools::convertDateformatToMomentjsFormat( 'Y-m-d' ) .'";' ) );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'splitView' );
		$this->buildToolbar( $Container );
		$this->buildSplitView( $Container->subordinate( 'div.headered' ) );
	}


	/** Baut die Toolbar
	 * @param \rsCore\Container $Container
	 */
	public function buildToolbar( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'header > div.row' );
		$Toolbar->subordinate( 'div.col-md-4 > input(button).btn btn-primary', array('data-toggle' => 'modal', 'data-target' => '#albumCreationModal', 'aria-hidden' => 'true', 'value' => t("New album")) );

		$Tabbar = $Toolbar->subordinate( 'div.col-md-8' );
		$this->buildTabBar( $Tabbar );
	}


	/** Baut die Tabbar zusammen
	 * @param \rsCore\Container $Container
	 */
	public function buildTabBar( \rsCore\Container $Container ) {
		$tabAttr = array('role' => 'tab', 'data-toggle' => 'tab');
		$metaAttr = array_merge( $tabAttr, array('data-target' => '#metaView') );
		$photosAttr = array_merge( $tabAttr, array('data-target' => '#photosView') );
		$Bar = $Container->subordinate( 'ul.nav.nav-tabs' );
		if( self::may('editAlbum') )
			$Bar->subordinate( 'li > a', $metaAttr, t("Album") );
		$Bar->subordinate( 'li > a', $photosAttr, t("Photos") );
	}


	/** Baut die SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildSplitView( \rsCore\Container $Container ) {
		$ModalSpace = $Container->subordinate( 'div.modal-space' );
		$Container = $Container->subordinate( 'div.row' );
		$ListColumn = $Container->subordinate( 'div.col-md-4.list' );
		$DetailColumn = $Container->subordinate( 'div.col-md-8.details' );

		$this->buildListView( $ListColumn );
		$this->buildDetailsView( $DetailColumn );

		if( self::may('addAlbum') )
			$this->buildCreationModal( $ModalSpace );
		$this->buildLightboxModal( $ModalSpace );
	}


	/** Baut die Listenansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildListView( \rsCore\Container $Container ) {
		$Table = $Container->subordinate( 'table.table#albumTable table-hover table-striped' );
		$Row = $Table->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', t("Name") );
		$Row->subordinate( 'th', t("Date") );
		$TableBody = $Table->subordinate( 'tbody' );
	}


	/** Baut die Detailansicht der SplitView
	 * @param \rsCore\Container $Container
	 */
	public function buildDetailsView( \rsCore\Container $Container ) {
		$TabContent = $Container->subordinate( 'div.tab-content' );
		$MetaView = $TabContent->subordinate( 'div.tab-pane#metaView' );
		$PhotosView = $TabContent->subordinate( 'div.tab-pane#photosView' );

		$this->buildMetaDetailsView( $MetaView );
		$this->buildPhotosDetailsView( $PhotosView );
	}


	/** Baut die Detailansicht der Album-Meta
	 * @param \rsCore\Container $Container
	 */
	public function buildMetaDetailsView( \rsCore\Container $Container ) {
		$DetailsView = $Container->subordinate( 'form', array('action' => 'save-album') );
		$DetailsView->subordinate( 'input(hidden):id' );

		$Title = $DetailsView->subordinate( 'div.title' );
		$Title->subordinate( 'h1', t("Details") );
		if( self::may('edit') )
			$Title->subordinate( 'button(button).btn.btn-primary.saveDetails', t("Save") );

		$Table = $DetailsView->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

/*		// @todo man könnte doch Alben einen Ort hinzufügen
		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Location") );
		$Row->subordinate( 'td', self::buildLocationSelector( 'locationId' ) );
*/

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Date") );
		$Row->subordinate( 'td' )
			->subordinate( 'div.input-group date' )
			->subordinate( 'input.form-control(text):date', array('placeholder' => t("Date"), 'data-dateformat' => \Site\Tools::convertDateformatToMomentjsFormat( 'Y-m-d' )) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );

		foreach( \Site\Tools::getAllowedLanguages() as $Language ) {
			$this->buildAlbumMetaForm( $DetailsView, $Language );
		}

		$Row = $DetailsView->subordinate( 'div.row' );
		if( self::may('delete') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-default.deleteAlbum', t("Delete") );
		if( self::may('edit') )
			$Row->subordinate( 'div.col-md-6 > button(button).btn.btn-primary.saveDetails', t("Save") );
	}


	/** Baut die Detailansicht der Fotos
	 * @param \rsCore\Container $Container
	 */
	public function buildPhotosDetailsView( \rsCore\Container $Container ) {
		$Dropzone = $Container->subordinate( 'div.upload-form > form.dropzone', array('action' => 'api.php/plugins/photos/upload', 'method' => 'post', 'enctype' => 'multipart/form-data') );
		$Dropzone->subordinate( 'input(hidden):id' );
		$Dropzone->subordinate( 'div.dz-message' )
			->subordinate( 'button(button).btn btn-primary', t("Choose file...") );
		$Dropzone->subordinate( 'div.fallback' )
			->subordinate( 'input(file):file', array('multiple' => 'multiple') )->parent()
			->subordinate( 'input(submit)='. t("Upload") );

		$DetailsView = $Container->subordinate( 'form', array('action' => 'save-album') );
		$DetailsView->subordinate( 'input(hidden):id' );

		$Grid = $DetailsView->subordinate( 'div.grid.row' );
	}


	/** Baut die Eingabe-Section für eine Sprache
	 * @param \rsCore\Container $Container
	 */
	public function buildAlbumMetaForm( \rsCore\Container $Container, \Brainstage\Language $Language ) {
		$Section = \Site\ToolsBackend::buildCollapsibleSection( $Container, $Language->name );
		$Section->addAttribute( 'class', 'in' );
		$Section->parent()->addAttribute( 'class', 'expanded album-meta language-'. $Language->shortCode );

		$Table = $Section->subordinate( 'table.table.table-striped.has-textfields' );
		$TableBody = $Table->subordinate( 'tbody' );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Title") );
		$Row->subordinate( 'td > input(text).form-control:title['. $Language->shortCode .']', array('placeholder' => t("Title")) );

		$Row = $TableBody->subordinate( 'tr' );
		$Row->subordinate( 'th', t("Description") );
		$Row->subordinate( 'td > textarea.form-control:description['. $Language->shortCode .']', array('placeholder' => t("Description")) );

		return $Section;
	}


	/** Baut die Eingabemaske
	 * @param \rsCore\Container $Container
	 */
	public function buildCreationModal( \rsCore\Container $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => 'new-album') );
		$Modal = $Form->subordinate( 'div#albumCreationModal.modal fade', array('aria-hidden' => 'true') )
						->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalHead = $Modal->subordinate( 'div.modal-header' );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFoot = $Modal->subordinate( 'div.modal-footer' );

		$ModalHead->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )
				->subordinate( 'span', array('aria-hidden' => 'true'), '&times;' );
		$ModalHead->subordinate( 'h1.modal-title', t("New album") );

		$ModalFoot->subordinate( 'button.btn btn-primary save-album', t("Save") );

		$Form = $ModalBody->subordinate( 'form' );

	#	$Form->subordinate( 'p', self::buildLocationSelector() );

		$DateFieldset = $Form->subordinate( 'div.row' );
		$DateFieldset->subordinate( 'div.col-md-6 > div.form-group > div.input-group date' )
			->subordinate( 'input.form-control(text):date', array(
					'placeholder' => t("Date"),
					'data-dateformat' => \Site\Tools::convertDateformatToMomentjsFormat( t('Y-m-d', 'Date with full year') )
				) )->parent()
			->subordinate( 'span.input-group-addon > span.glyphicon glyphicon-calendar' );
	}


	/** Baut die Lightbox
	 * @param \rsCore\Container $Container
	 */
	public function buildLightboxModal( \rsCore\Container $Container ) {
		$Modal = $Container->subordinate( 'div#lightboxModal.modal fade', array('aria-hidden' => 'true') )
						->subordinate( 'div.modal-dialog > div.modal-content' );
		$ModalBody = $Modal->subordinate( 'div.modal-body' );
		$ModalFoot = $Modal->subordinate( 'div.modal-footer > div.row' );

		$ModalBody->subordinate( 'div.imageFrame' );

		$MetaTable = $ModalFoot->subordinate( 'div.col-md-8 > table.table.table-condensed' );
		$Buttons = $ModalFoot->subordinate( 'div.col-md-4' );

		$Row = $MetaTable->subordinate( 'thead > tr' );
		$Row->subordinate( 'th', t("File name") );
		$Row->subordinate( 'th', t("Owner") );
		$Row->subordinate( 'th', t("Date") );
		$Row->subordinate( 'th', t("Size") );

		$Row = $MetaTable->subordinate( 'tbody > tr' );
		$Row->subordinate( 'td.filename' );
		$Row->subordinate( 'td.owner' );
		$Row->subordinate( 'td.date' );
		$Row->subordinate( 'td.filesize' );

		$Buttons->subordinate( 'a.btn btn-default download', t("Download") );
		$Buttons->subordinate( 'a.btn btn-primary closeModal', t("Close") );
	}


/* API Plugin */

	/** Fügt ein neues Album ein
	 * @return boolean
	 */
	public function api_addAlbum( $params ) {
		self::throwExceptionIfNotPrivileged( 'addAlbum' );
		$Album = \Site\PhotoAlbum::createAlbum();
		if( $Album ) {
			if( strlen( $params['date'] ) > 0 )
				$Album->date = \DateTime::createFromFormat( t('Y-m-d', 'Date with full year'), $params['date'] );
			return $Album->adopt();
		}
		return false;
	}


	/** Listet die Albums auf
	 * @return array
	 */
	public function api_listAlbums( $params ) {
		$albums = array();
		foreach( array_reverse( \Site\PhotoAlbum::getAlbums() ) as $Album ) {
			$array = $Album->getColumns();
			$array['date'] = $Album->date->format( t('Y-m-d', 'Date with full year') );
			$languages = array();
			foreach( \Site\Tools::getAllowedLanguages() as $Language ) {
				$Meta = $Album->getMeta( $Language );
				$languages[ $Language->shortCode ] = $Meta ? $Meta->getColumns() : null;
			}
			$array['languages'] = $languages;
			$albums[] = $array;
		}
		return $albums;
	}


	/** Speichert Veranstaltungsdetails
	 * @return array
	 */
	public function api_saveAlbum( $params ) {
		self::throwExceptionIfNotPrivileged( 'editAlbum' );
		$Album = \Site\PhotoAlbum::getAlbumById( postVar('id') );
		if( !$Album )
			return false;

		$fields = array();
		foreach( $fields as $field ) {
			if( isset( $_POST[ $field ] ) ) {
				$value = postVar( $field );
				$Album->set( $field, $value );
			}
		}

		if( strlen( postVar('date', '') ) > 0 )
			$Album->date = \DateTime::createFromFormat( t('Y-m-d', 'Date with full year'), postVar('date') );

		$titles = postVar( 'title', array() );
		$descriptions = postVar( 'description', array() );
		foreach( \Site\Tools::getAllowedLanguages() as $Language ) {
			$Meta = $Album->getMeta( $Language );
			$Meta->title = $titles[ $Language->shortCode ];
			$Meta->description = $descriptions[ $Language->shortCode ];
			$Meta->adopt();
		}

		return $Album->getColumns();
	}


	/** Löscht ein Album
	 * @return boolean
	 * @todo Prüfen ob das Album auch im Zuständigkeitsbereich liegt und gelöscht werden darf
	 */
	public function api_deleteAlbum( $params ) {
		self::throwExceptionIfNotPrivileged( 'deleteAlbum' );
		$Album = \Site\Album::getAlbumById( postVar('id') );
		if( $Album )
			return $Album->remove();
	}


	/** Lädt ein neues Photo hoch
	 * @return boolean
	 */
	public function api_uploadPhoto( $params ) {
		self::throwExceptionIfNotPrivileged( 'upload' );
		$Album = \Site\PhotoAlbum::getAlbumById( $_POST['id'] );
		if( !$Album )
			return false;
		$FileManager = new \rsCore\FileManager( null, true );
		$uploadedFiles = $FileManager->handleUploads();
		$files = array();
		foreach( $uploadedFiles as $File ) {
			if( $File ) {
				$Photo = \Site\Photo::addPhoto( $Album, $File );
				if( $Photo )
					$files[] = $Photo->getColumns();
			}
		}
		return $files;
	}


	/** Fügt ein neues Photo ein
	 * @return boolean
	 */
	public function api_addPhoto( $params ) {
		self::throwExceptionIfNotPrivileged( 'addPhoto' );
	}


	/** Listet die Photos auf
	 * @return array
	 */
	public function api_listPhotos( $params ) {
		$Album = \Site\PhotoAlbum::getAlbumById( $params['id'] );
		if( !$Album )
			return false;
		$photos = array();
		foreach( $Album->getFiles() as $File ) {
			$array = array();
			$array['id'] = $File->id;
			$array['uploadDate'] = $File->uploadDate->format( t('Y-m-d H:i', 'Date and Time: full year, without seconds') );
			$array['title'] = $File->title;
			$array['filename'] = $File->filename;
			$array['type'] = $File->type;
			$array['size'] = $File->size;
			$array['readableSize'] = \rsCore\Core::functions()->readableFileSize( $File->size );
			$array['ownerName'] = $File->getUser() ? $File->getUser()->name : null;
			$photos[] = $array;
		}
		return $photos;
	}


	/** Entfernt ein Photo aus dem Album
	 * @return boolean
	 */
	public function api_removePhoto( $params ) {
		self::throwExceptionIfNotPrivileged( 'removePhoto' );
		$Relation = \Site\Photo::getPhotoById( postVar('id') );
		if( $Relation )
			return $Relation->remove();
		return false;
	}


	/** Löscht ein Photo und entfernt es aus allen Alben
	 * @return boolean
	 */
	public function api_deletePhoto( $params ) {
		self::throwExceptionIfNotPrivileged( 'deletePhoto' );
		$Photo = \Site\Photo::getPhotoById( postVar('id') );
		if( $Photo ) {
			$File = $Photo->getFile();
			foreach( \Site\Photo::getRelationsByFile( $File ) as $Relation )
				$Relation->remove();
		}
		return false;
	}


}