<?php
namespace Plugins;


interface ConverterInterface {
	
	static function getPlugins();
	static function getPluginInterfaceName();

	function exportARFF( $text );
	
}


class Converter extends \rsCore\Plugin implements ConverterInterface {


	const PLUGIN_DIR = '../classes/Features/';
	const PLUGIN_NAMESPACE = 'Features';
	const PLUGIN_INTERFACE = 'Plugin';


	private static $_FeaturesFramework;
	private static $_plugins;


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

		$Framework->registerHook( $Plugin, 'upload', 'api_upload' );
	}


	/** Wird von Brainstage aufgerufen, um abzufragen, welche Rechtebezeichner vom Plugin verwendet werden
	 *
	 * @return array
	 */
	public static function registerPrivileges() {
		return 'upload';
	}


	/** Dient als Konstruktor-Erweiterung
	 */
	protected function init() {
		parent::init();
		self::initPlugins();
	}


	/** Gibt das Features-Framework zurück
	 * @return Framework
	 */
	protected static function getFeaturesFramework() {
		if( !self::$_FeaturesFramework ) {
			self::$_FeaturesFramework = new \rsCore\Framework();
			self::initPlugins();
		}
		return self::$_FeaturesFramework;
	}


	/** Gibt den Namen des PluginInterfaces zurück
	 * @return string
	 */
	public static function getPluginInterfaceName() {
		return '\\'. self::PLUGIN_NAMESPACE .'\\' . self::PLUGIN_INTERFACE;
	}


/* Features Framework */

	/** Lädt und initialisiert sämtliche Plugins
	 *
	 * @access protected
	 * @return void
	 */
	protected static function initPlugins() {
		foreach( self::getPlugins() as $pluginName ) {
			try {
				$Plugin = $pluginName::featureRegistration( self::getFeaturesFramework() );
				self::$_plugins[ $pluginName ] = $Plugin;
			} catch( \Exception $Exception ) {
				\rsCore\ErrorHandler::catchException( $Exception );
			}
		}
	}


	/** Lädt sämtliche Plugins
	 *
	 * @access public
	 * @return array
	 */
	public static function getPlugins() {
		return self::loadFeaturesPlugins();
	}


	/** Lädt interne Brainstage-Plugins
	 *
	 * @return array
	 */
	protected static function loadFeaturesPlugins() {
		$plugins = array();
		$namespace = '\\'. self::PLUGIN_NAMESPACE .'\\';
		foreach( scandir( self::PLUGIN_DIR ) as $fileName ) {
			$filePath = self::PLUGIN_DIR .'/'. $fileName;
			if( is_file( $filePath ) ) {
				$fileNameComponents = explode( '.', $fileName );
				if( array_pop( $fileNameComponents ) == 'php' ) {
					$pluginName = $namespace . $fileNameComponents[0];
					include_once( $filePath );
					$Reflection = new \ReflectionClass( $pluginName );
					if( $Reflection->isSubclassOf( $namespace . self::PLUGIN_INTERFACE ) ) {
						$plugins[] = $pluginName;
					}
				}
			}
		}
		return $plugins;
	}


/* Brainstage Plugin */

	/** Ergänzt den Navigator
	 * @return string
	 */
	public function getNavigatorItem() {
		return t("Converter");
	}


	/** Ergänzt den Header
	 * @param \rsCore\ProtectivePageHead $Head
	 */
	public function buildHead( \rsCore\ProtectivePageHead $Head ) {
		$Head->linkStylesheet( '/static/css/converter.css' );
		$Head->linkScript( '/static/js/converter.js' );
	}


	/** Ergänzt den MainContent
	 * @param \rsCore\Container $Container
	 */
	public function buildBody( \rsCore\Container $Container ) {
		$Dropzone = $Container->subordinate( 'div.upload-form > form.dropzone', array('action' => 'api.php/plugins/converter/upload', 'method' => 'post', 'enctype' => 'multipart/form-data') );
		$Dropzone->subordinate( 'input(hidden):id' );
		$FormatSelect = $Dropzone->subordinate( 'p > select:format' );
		$Dropzone->subordinate( 'div.dz-message' )
			->subordinate( 'button(button).btn btn-primary', t("Choose file...") );
		$Dropzone->subordinate( 'div.fallback' )
			->subordinate( 'input(file):file' )->parent()
			->subordinate( 'input(submit)='. t("Upload") );
			
		$FormatSelect->subordinate( 'option=arff', "ARFF" );
		$FormatSelect->subordinate( 'option=chunker', "Chunker" );

		$Output = $Container->subordinate( 'div.row > div.col-xs-12 > textarea.form-control:output', array('rows' => 40) );
	}


/* API Plugin */

	/** Lädt ein neues Photo hoch
	 * @return boolean
	 */
	public function api_upload( $params ) {
		self::throwExceptionIfNotPrivileged( 'upload' );
		$FileManager = new \rsCore\FileManager( null, true );
		$uploadedFiles = $FileManager->handleUploads();
		$result = array();
		$contents = array();
		foreach( $uploadedFiles as $File ) {
			if( $File ) {
				$contents[] = $File->getFileContents();
				$result['files'][] = $File->getColumns();
				$File->remove();
			}
		}
		$result['export'] = $this->export( $contents );
		return $result;
	}


/* Export & conversion */

	/** Exportiert
	 * @return string
	 */
	public function export( $text ) {
		if( postVar('format') == 'arff' )
			return $this->exportARFF( $text );
		if( postVar('format') == 'chunker' )
			return $this->exportChunker( $text );
	}


	/** Exportiert ins ARFF-Format
	 * @return string
	 */
	public function exportARFF( $text ) {
		$chunkerOutput = $text;
		$annotatedSentences = '';
		if( is_array($text) ) {
			$chunkerOutput = '';
			$files = $text;
			foreach( $files as $filecontent ) {
				if( strpos( $filecontent, '<T>' ) !== false && strpos( $filecontent, '</T>' ) !== false ) {	// annotierter Text mit Titel-Tags
					$annotatedSentences = $filecontent;
				} elseif( strpos( $filecontent, '<NC>' ) !== false && strpos( $filecontent, '</NC>' ) !== false ) {	// Chunker-Ausgabe
					$chunkerOutput = $filecontent;
				}
			}
		}
		
		$ARFF = new \Formatter\ARFF( 'relation', $chunkerOutput, $annotatedSentences );
		
		foreach( self::$_plugins as $pluginName => $Plugin ) {
			$attributeName = @array_pop( @explode( '\\', $pluginName ) );
			$ARFF->addAttribute( $attributeName, $pluginName::getDatatype( $ARFF ), array($Plugin, 'getValueForToken') );
		}

		return $ARFF->convert();
	}


	/** Exportiert für den Chunker
	 * @return string
	 */
	public function exportChunker( $text ) {
		$text = preg_replace( "/<\/?T>/", "", $text );
		$lines = explode( "\n", $text );
		$sentences = array();
		foreach( $lines as $line ) {
			$sentences[] = '<s>'. trim( $line ) .'</s>';
		}
		$output = implode( "\n", $sentences );

		return $output;
	}


}