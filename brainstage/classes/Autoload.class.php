<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
function __autoload( $className ) {
	$filepath = Autoload::load( $className );
	if( $filepath ) {
		require_once( $filepath );
		Autoload::preinit( $className );
	}
}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface AutoloadInterface {

	public static function load( $className );
	public static function getLoadedClasses();
	public static function getPlugins( $onlyActivatedPlugins=true );
	public static function getTemplates( $projectTemplates );

}


/**
 * @author Robert Sass <rs@braiedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Autoload implements AutoloadInterface {


	const FRAMEWORK_REGISTRATION_INTERFACE = '\rsCore\CoreFrameworkInitializable';
	const PLUGIN_INTERFACE = '\rsCore\PluginInterface';
	const TEMPLATE_INTERFACE = '\rsCore\BaseTemplateInterface';

	const PLUGIN_NAMESPACE = 'Plugins';
	const TEMPLATE_NAMESPACE = 'Templates';


	private static $_classes;


	private static function getReflection( $className ) {
		return new \ReflectionClass( $className );
	}


	public static function preinit( $className ) {
		self::callFrameworkRegistration( $className);
	}


	private static function callFrameworkRegistration( $className ) {
		$interfaceName = self::FRAMEWORK_REGISTRATION_INTERFACE;
		$Reflection = self::getReflection( $className );
		if( $Reflection->isSubclassOf( $interfaceName ) )
			$className::frameworkRegistration();
	}


	public static function load( $className ) {
		if( !self::$_classes )
			self::$_classes = array();

		$classPath = '';
		$namespacePath = explode( '\\', $className );
		if( $namespacePath[0] == self::TEMPLATE_NAMESPACE ) {
			$className = str_replace( self::TEMPLATE_NAMESPACE .'\\', '', $className );
			$filepath = self::tryLoadingTemplate( $className, $classPath );
			if( $filepath )
				return $filepath;
		}
		elseif( $namespacePath[0] == self::PLUGIN_NAMESPACE ) {
			$className = str_replace( self::PLUGIN_NAMESPACE .'\\', '', $className );
			$filepath = self::tryLoadingPlugin( $className, $classPath );
			if( $filepath )
				return $filepath;
		}
		else {
			$className = array_pop( $namespacePath );
			$classPath = join( '/', $namespacePath ) .'/';
		}

		$filepath = self::tryLoadingClass( $className, $classPath );
		if( $filepath )
			return $filepath;

		return $filepath;
	}


	private static function tryLoadingTemplate( $className, $namespacePath=null ) {
		$classPath = $namespacePath === null ? $className : $namespacePath . $className;

		$possibleLocations = array(
			'classes/'. self::TEMPLATE_NAMESPACE .'/'. $classPath .'.template.php',
			'../classes/'. self::TEMPLATE_NAMESPACE .'/'. $classPath .'.template.php',
		);

		foreach( $possibleLocations as $filepath ) {
			if( file_exists( $filepath ) ) {
				self::$_classes[] = $className;
				return $filepath;
			}
		}
		return null;
	}


	private static function tryLoadingPlugin( $className, $namespacePath=null ) {
		$classPath = $namespacePath === null ? $className : $namespacePath . $className;

		$possibleLocations = array(
			'plugins/'. $classPath .'.plugin.php',
			'../plugins/'. $classPath .'.plugin.php'
		);

		foreach( $possibleLocations as $filepath ) {
			if( file_exists( $filepath ) ) {
				self::$_classes[] = $className;
				return $filepath;
			}
		}
		return null;
	}


	private static function tryLoadingClass( $className, $namespacePath=null ) {
		$classPath = $namespacePath === null ? $className : $namespacePath . $className;

		$possibleLocations = array(
			'classes/'. $classPath .'.class.php',
			'classes/'. $classPath .'.interface.php',
			'classes/'. $classPath .'.template.php',
			'brainstage/classes/'. $classPath .'.class.php',
			'brainstage/classes/'. $classPath .'.interface.php',
			'../classes/'. $classPath .'.class.php',
			'../classes/'. $classPath .'.interface.php'
		);

		foreach( $possibleLocations as $filepath ) {
			if( file_exists( $filepath ) ) {
				self::$_classes[] = $className;
				return $filepath;
			}
		}
		return null;
	}


	public static function getLoadedClasses() {
		return self::$_classes;
	}


	public static function getPlugins( $onlyActivatedPlugins=true ) {
		$pluginDirectory = 'classes/'. self::PLUGIN_NAMESPACE .'/';
		if( !is_dir( $pluginDirectory ) )
			$pluginDirectory = '../'. $pluginDirectory;
			
		$pluginNames = array();
		
		if( $onlyActivatedPlugins ) {
			$ActivatedPluginsSetting = \Brainstage\Setting::getTextSetting( 'Brainstage/Plugins' );
			$activatedPlugins = $ActivatedPluginsSetting ? json_decode( $ActivatedPluginsSetting->value ) : array();
		}
		
		if( is_dir( $pluginDirectory ) ) {
			foreach( scandir( $pluginDirectory ) as $fileName ) {
				$filePath = $pluginDirectory .'/'. $fileName;
				if( is_file( $filePath ) ) {
					
					$fileNameComponents = explode( '.', $fileName );
					if( count( $fileNameComponents ) == 3 && $fileNameComponents[2] == 'php' && $fileNameComponents[1] == 'plugin' ) {
						
						$className = '\\'. self::PLUGIN_NAMESPACE .'\\'. $fileNameComponents[0];
						
						if( !$onlyActivatedPlugins || in_array( $className, $activatedPlugins ) ) {
							
							include_once( $filePath );
							
							$Reflection = self::getReflection( $className );
							
							if( $Reflection->isSubclassOf( self::PLUGIN_INTERFACE ) )
								$pluginNames[] = $className;
						}
					}
					
				}
			}
		}
		
		return $pluginNames;
	}


	public static function getTemplates( $projectTemplates=true ) {
		$templateDirectory = 'classes/'. self::TEMPLATE_NAMESPACE .'/';
		if( !is_dir( $templateDirectory ) || ($projectTemplates && strpos( $templateDirectory, 'brainstage' ) >= 0) )
			$templateDirectory = '../'. $templateDirectory;
		$templateNames = array();
		if( is_dir( $templateDirectory ) ) {
			foreach( scandir( $templateDirectory ) as $fileName ) {
				$filePath = $templateDirectory .'/'. $fileName;
				if( is_file( $filePath ) ) {
					$fileNameComponents = explode( '.', $fileName );
					if( count( $fileNameComponents ) == 3 && $fileNameComponents[2] == 'php' && $fileNameComponents[1] == 'template' ) {
						$className = '\\'. self::TEMPLATE_NAMESPACE .'\\'. $fileNameComponents[0];
						try {
							include_once( $filePath );
							$Reflection = self::getReflection( $className );
							if( $Reflection->isSubclassOf( self::TEMPLATE_INTERFACE ) )
								$templateNames[] = $className;
						} catch( \Exception $Exception ) {}
					}
				}
			}
		}
		return $templateNames;
	}

}
