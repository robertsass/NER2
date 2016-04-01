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
interface DocumentsInterface extends PluginInterface {

	function api_listDocuments( $params );
	function api_createDocument( $params );
	function api_getDocument( $params );
	function api_saveDocument( $params );
	function api_moveDocument( $params );
	function api_deleteDocument( $params );
	function api_listTemplates( $params );
	function api_listTags( $params );

}


/** DocumentsPlugin
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\Plugin
 */
class Documents extends \Brainstage\Plugin implements DocumentsInterface {


	/** Wird von Brainstage aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param Framework $Framework
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
		return 95;
	}


	/** Wird von der API aufgerufen, damit sich das Plugin für Hooks registrieren kann
	 *
	 * @param \rsCore\FrameworkInterface $Framework
	 */
	public static function apiRegistration( \rsCore\FrameworkInterface $Framework ) {
		$Plugin = self::instance();
		$Framework->registerHook( $Plugin, 'list', 'api_listDocuments' );
		$Framework->registerHook( $Plugin, 'create', 'api_createDocument' );
		$Framework->registerHook( $Plugin, 'get', 'api_getDocument' );
		$Framework->registerHook( $Plugin, 'save', 'api_saveDocument' );
		$Framework->registerHook( $Plugin, 'move', 'api_moveDocument' );
		$Framework->registerHook( $Plugin, 'delete', 'api_deleteDocument' );
		$Framework->registerHook( $Plugin, 'templates', 'api_listTemplates' );
		$Framework->registerHook( $Plugin, 'tags', 'api_listTags' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return array(
			'roots' => 'documents',
			'create',
			'edit',
			'reorder',
			'change-templates',
			'delete',
			'templates' => 'list',
		);
	}


/* Private methods */

	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
	}


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return self::t("Documents");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkScript( 'static/js/documents.js' );
		$Head->linkScript( 'static/js/ace/ace.js' );
		$Head->linkScript( 'static/js/pen.js' );
		$Head->linkStylesheet( 'static/css/pen.css' );
		$Head->linkStylesheet( 'static/css/fonts.css' );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Toolbar = $Container->subordinate( 'header > div.toolbar > div.row' );
		$Colset = $Container->subordinate( 'div.colset' );

		$userRoot = 0;
		if( user()->isSuperAdmin() ) {
			$userRoot = \Brainstage\Document::getDatabaseConnection()->getRoot(false);
			if( $userRoot )
				$userRoot = $userRoot->getPrimaryKeyValue();
		} else {
			$userRoots = array();
			foreach( self::getUserRight( 'roots' ) as $Right ) {
				foreach( explode( ',', $Right->value ) as $nodeId ) {
					$userRoots[] = intval( trim( $nodeId ) );
				}
			}
			if( count( $userRoots ) <= 1 )
				$userRoot = intval( trim( current( $userRoots ) ) );
		}

		$DocumentsBrowser = $Colset->subordinate( 'div#content.full-content col-0 > ul.browser editable', array('data-root' => $userRoot) );
		$this->buildToolbar( $Toolbar, isset($userRoots) ? $userRoots : array() );
	}


	/** Baut die Toolbar
	 * @param \rsCore\Container $Container
	 */
	public function buildToolbar( \rsCore\Container $Container, $userRoots=array() ) {
		if( empty( $userRoots ) ) {
			$RootNode = \Brainstage\Document::getDatabaseConnection()->getRoot();
			$title = $RootNode->getName();
		} else {
			$title = array();
			foreach( $userRoots as $nodeId ) {
				$Node = \Brainstage\Document::getDocumentById( $nodeId, null );
				if( $Node )
					$title[] = $Node->getName();
			}
			$title = join( ', ', $title );
		}
		$Container->subordinate( 'div.col-md-9 > h1 > a', array('href' => '../', 'target' => '_blank'), $title );
		$this->buildLanguageSelector( $Container->subordinate( 'div.col-md-3' ) );
	}


	/** Baut den Sprach-Umschalter
	 * @param \rsCore\Container $Container
	 */
	public function buildLanguageSelector( \rsCore\Container $Container ) {
		$usersLanguages = array_keys( \rsCore\Useragent::detectLanguages() );
		$preselectedLanguage = \rsCore\Localization::extractLanguageCode( current( $usersLanguages ) );

		$LanguageSelector = $Container->subordinate( 'select.languageSelector selectize' );
		foreach( \Brainstage\Language::getLanguages() as $Language ) {
				$attr = array('value' => $Language->shortCode);
				if( $Language->shortCode == $preselectedLanguage )
					$attr['selected'] = 'selected';
			$LanguageSelector->subordinate( 'option', $attr, $Language->name );
		}
	}


/* API Plugin */

	/** Gibt den Dokumenten-Baum zurück
	 * @param array $params
	 * @return array
	 */
	public function api_listDocuments( $params ) {
		self::throwExceptionIfNotAuthorized();

		$trees = array();
		if( user()->isSuperAdmin() )
			$trees[] = \Brainstage\Document::getDatabaseConnection()->constructTree();
		else {
			$userRoots = self::getUserRight( 'roots' );
			foreach( $userRoots as $Right ) {
				foreach( explode( ',', $Right->value ) as $nodeId ) {
					$nodeId = intval( trim( $nodeId ) );
					$StartNode = \Brainstage\Document::getByPrimaryKey( $nodeId );
					$trees[] = \Brainstage\Document::getDatabaseConnection()->constructTree( $StartNode );
				}
			}
		}
		$ExtendedTree = self::supplementDocumentsTree( $trees, $params['language'] );
		if( count( $ExtendedTree ) == 1 /* && ( user()->isSuperAdmin() || $userRoots == 1 ) */ ) {
		#	if( $ExtendedTree[0][ \rsCore\DatabaseNestedSet::FIELDNAME_LEVEL ] == 0 )
				return $ExtendedTree[0]['children'];
		}
		return $ExtendedTree;
	}


	/** Gibt den Dokumenten-Baum zurück
	 * @param array $params
	 * @return array
	 */
	public function api_createDocument( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			$Document = \Brainstage\Document::getDatabaseConnection()->createChild( $params['parent'] );
			$Version = $Document->newVersion( $params['language'] );
			$Version->name = self::t("Untitled");
			$Version->content = '';
			$Version->adopt();
			return $Document->getColumns();
		}
	}


	/** Gibt ein Dokument zurück
	 * @param array $params
	 * @return array
	 */
	public function api_getDocument( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			$Document = \Brainstage\Document::getDocumentById( $params['id'], null );
			$details = $Document->getArray();

			$versions = array();
			foreach( $Document->getLanguages() as $languageCode => $Language ) {
				$Version = \Brainstage\DocumentVersion::getByDocument( $Document, $languageCode );
				if( $Version ) {
					$versions[ $languageCode ] = $Version->getColumns();
					unset( $versions[ $languageCode ]['id'] );
					unset( $versions[ $languageCode ]['language'] );
					unset( $versions[ $languageCode ]['userId'] );
					unset( $versions[ $languageCode ]['documentId'] );

					$versions[ $languageCode ]['tags'] = array();
					foreach( \Brainstage\DocumentTag::getTagsByDocument( $Document, $languageCode ) as $Tag )
						$versions[ $languageCode ]['tags'][] = $Tag->name;

					$versions[ $languageCode ]['parents'] = array();
					foreach( $Document->getParents() as $Parent ) {
						$Parent->setLanguage( $languageCode );
						if( !$Parent->isRoot() )
							$versions[ $languageCode ]['parents'][] = $Parent->getName();
					}
//					$Document->setLanguage( $languageCode );
//					$versions[ $languageCode ]['url'] = $Document->getComposedUrl();
				}
//				$details['languages'][ $languageCode ] = $Language->name;
			}
			$details['versions'] = $versions;

			foreach( \Brainstage\Language::getLanguages() as $Language )
				$details['languages'][ $Language->shortCode ] = $Language->name;

			$details['path'] = self::getDocumentsPaths( $Document );
			$details['url'] = $Document->getComposedUrl();
			
			return $details;
		}
	}


	/** Speichert ein Dokument
	 * @param array $params
	 * @return array
	 */
	public function api_saveDocument( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			$params = $_POST;
			$Document = \Brainstage\Document::getDocumentById( $params['id'], $params['language'] );
			$Version = $Document->newVersion( $params['language'] );
			$Version->userId = user()->id;

			$fields = array('accessibility');
			foreach( $fields as $field ) {
				if( array_key_exists( $field, $params ) )
					$Document->set( $field, $params[ $field ] );
			}

			if( array_key_exists( 'template', $params ) )
				$Document->templateName = $params['template'];

			if( array_key_exists( 'pathcomponent', $params ) ) {
				$PathComponent = \Brainstage\DocumentPathComponent::add( $Document, $params['language'], $params['pathcomponent'] );
				$PathComponent->pathComponent = $params['pathcomponent'];
			}

			$fields = array('name', 'content', 'language');
			foreach( $fields as $valueField => $field ) {
				if( array_key_exists( $field, $params ) )
					$Version->set( $field, $params[ $field ] );
			}

			if( array_key_exists( 'editor', $params ) )
				$Version->editorType = $params['editor'];

/*
			if( array_key_exists( 'template', $params ) )
				$Document->templateName = $params['template'];
			if( array_key_exists( 'accessibility', $params ) )
				$Document->templateName = $params['accessibility'];

			if( array_key_exists( 'name', $params ) )
				$Version->name = $params['name'];
			if( array_key_exists( 'content', $params ) )
				$Version->content = $params['content'];
			if( array_key_exists( 'language', $params ) )
				$Version->language = $params['language'];
*/

			return $Document->adopt() && $Version->adopt();
		}
	}


	/** Verschiebt ein Dokument
	 * @param array $params
	 * @return array
	 */
	public function api_moveDocument( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			if( array_key_exists('id', $params) && array_key_exists('direction', $params) ) {
				$Document = \Brainstage\Document::getById( $params['id'] );
				if( $params['direction'] == 'left' )
					return $Document->moveLeft();
				elseif( $params['direction'] == 'right' )
					return $Document->moveRight();
				elseif( $params['direction'] == 'up' )
					return $Document->moveUp();
				elseif( $params['direction'] == 'down' )
					return $Document->moveDown();
			}

			elseif( array_key_exists('targetItems', $params) ) {
				$nodeId = intval( $params['node'] );
				$targetNodeId = intval( $params['targetNode'] );
				$targetItems = explode( ',', $params['targetItems'] );
				$Node = \Brainstage\Document::getById( $nodeId );
				$TargetNode = \Brainstage\Document::getById( $targetNodeId );
				if( !$Node || !$TargetNode )
					return false;
				$distance = ($Node->getLevel()-1) - $TargetNode->getLevel();
				if( $distance > 0 ) {
					while( $distance > 0 ) {
						$Node->moveUp();
						$Node = \Brainstage\Document::getById( $nodeId );
						$distance--;
					}
					try {
						for( $j=0; $j<9999; $j++ ) {
							$Node->moveLeft();
						}
					} catch( \Exception $Exception ) {}
				} elseif( $distance < 0 ) {
					while( $distance < 0 ) {
						$Node->moveDown();
						$distance++;
					}
					try {
						for( $j=0; $j<9999; $j++ ) {
							$Node->moveLeft();
							$Node = \Brainstage\Document::getById( $nodeId );
							if( $Node->getLeftValue() == $TargetNode->getRightValue()+1 )
								break;
						}
					} catch( \Exception $Exception ) {}
					$Node->moveDown();
				} else {
					try {
						foreach( $targetItems as $nodeId )
							$Node->moveLeft();
					} catch( \Exception $Exception ) {}
				}
				try {
					foreach( $targetItems as $nodeId ) {
						if( intval( trim( $nodeId ) ) == $Node->getPrimaryKeyValue() )
							break;
						$Node->moveRight();
					}
				} catch( \Exception $Exception ) {}
				return true;
			}
		}
		return false;
	}


	/** Verschiebt ein Dokument
	 * @param array $params
	 * @return array
	 */
	public function api_deleteDocument( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			$Document = \Brainstage\Document::getDocumentById( $params['id'] );
			return $Document->removeDocument();
		}
	}


	/** Listet alle verfügbaren Templates auf
	 * @param array $params
	 * @return array
	 */
	public function api_listTemplates( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			$templateNames = array();
			$allowedTemplates = array();
			foreach( self::getUserRight( 'templates', true ) as $Right )
				$allowedTemplates = array_merge( $allowedTemplates, $Right->getList() );
			$allowedTemplates = array_unique( $allowedTemplates );
			foreach( \Autoload::getTemplates( true ) as $templateClassName ) {
				$templateClassName = explode( '\\', $templateClassName );
				$templateName = array_pop( $templateClassName );
				if( user()->isSuperAdmin() || in_array( $templateName, $allowedTemplates ) )
					$templateNames[] = $templateName;
			}
			return $templateNames;
		}
	}


	/** Listet alle verfügbaren Tags auf, ggf. gefiltert nach Sprache
	 * @param array $params
	 * @return array
	 */
	public function api_listTags( $params ) {
		self::throwExceptionIfNotAuthorized();
		if( isLoggedin() ) {
			$tagNames = array();
			if( isset( $params['language'] ) )
				$tags = \Brainstage\Tag::getByColumn( 'language', $params['language'], true );
			else
				$tags = \Brainstage\Tag::getAll();
			foreach( $tags as $Tag ) {
				if( isset( $params['language'] ) )
					$tagNames[] = $Tag->name;
				else
					$tagNames[ $Tag->language ][] = $Tag->name;
			}
			return $tagNames;
		}
	}


/* Private methods */

	/** Erweitert den Dokumenten-Baum jeweils um die Datensätze der aktuellsten Versionen
	 * @param array $subtree
	 * @return array
	 */
	private static function supplementDocumentsTree( array $subtree, $language=null ) {
		foreach( $subtree as $index => $node ) {
			$documentId = $node['id'];
			$Document = \Brainstage\Document::getDocumentById( $documentId, $language );
			$Version = $Document->getCurrentVersion();
			if( !$Version )
				$Version = $Document->newVersion( $language );
			if( $Version ) {
				$subtree[ $index ]['name'] = $Version->name;
				$subtree[ $index ]['content'] = $Version->content;
				$subtree[ $index ]['timestamp'] = $Version->timestamp;
			}
			$subtree[ $index ]['tags'] = array();
			foreach( \Brainstage\DocumentTag::getTagsByDocument( $Document, $language ) as $Tag )
				$subtree[ $index ]['tags'][] = $Tag->name;
			if( is_array( $node['children'] ) )
				$subtree[ $index ]['children'] = self::supplementDocumentsTree( $node['children'], $language );
			$subtree[ $index ]['path'] = self::getDocumentsPaths( $Document );
			$subtree[ $index ]['url'] = $Document->getComposedUrl();
		}
		return $subtree;
	}


	/** Gibt die Pfade des Dokuments je Sprache zurück
	 * @param \Brainstage\Document
	 * @return array
	 */
	private static function getDocumentsPaths( \Brainstage\Document $Document ) {
		$path = array();
		foreach( $Document->getLanguages() as $languageCode => $Language ) {
			$pathOrigin = '';
			try {
				$Parent = $Document->getParent();
				$pathOrigin = $Parent->getComposedPath( $Language );
			} catch( \Exception $Exception ) {}
			$path[ $languageCode ]['origin'] = rtrim( \rsCore\Core::getSiteUrl() . $pathOrigin, '/' ) .'/';
			$path[ $languageCode ]['component'] = strval( $Document->getPathComponent( $Language ) );
		}
		return $path;
	}


}