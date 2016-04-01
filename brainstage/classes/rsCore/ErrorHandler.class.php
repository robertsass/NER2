<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace rsCore;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface ErrorHandlerInterface {

	static function activate( $redirectUrl );
	static function catchException( \Exception $Exception );
	static function catchError( $code, $message, $file, $line );
	static function catchFatalError();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class ErrorHandler implements ErrorHandlerInterface {


	private static $_errorRedirect;


	/** Registriert diverse Error-Handler um sämtliche Fehler aufzufangen und zu protokollieren
	 * @api
	 */
	public static function activate( $redirectUrl=null ) {
		self::$_errorRedirect = $redirectUrl;
		self::handshakeClasses();
		set_error_handler( array( __CLASS__, 'catchError' ) );
		set_exception_handler( array( __CLASS__, 'catchException' ) );
		register_shutdown_function( array( __CLASS__, 'catchFatalError' ) );
	}


	/** Fängt eine Exception auf und protokolliert diese
	 * @param \Exception $Exception
	 * @return \Brainstage\ExceptionLog
	 * @api
	 */
	public static function catchException( \Exception $Exception ) {
		$Log = \Brainstage\ExceptionLog::createLogEntry();
		if( $Log ) {
			$Log->title = $Exception->getMessage();
			$Log->text = $Exception->getTraceAsString();
			$Log->file = $Exception->getFile();
			$Log->line = $Exception->getLine();
			$Log->adopt();
			self::redirect( $Log );
		}
		return $Log;
	}


	/** Fängt einen Error auf und protokolliert ihn
	 * @param integer $code
	 * @param string $message
	 * @param string $file
	 * @param integer $line
	 * @return \Brainstage\ExceptionLog
	 * @api
	 */
	public static function catchError( $code, $message, $file, $line ) {
		$Log = \Brainstage\ExceptionLog::createLogEntry();
		if( $Log ) {
			$Log->title = $message;
			$Log->text = $message;
			$Log->file = $file;
			$Log->line = $line;
			$Log->adopt();
		#	self::redirect( $Log );
		}
		return $Log;
	}


	/** Fängt einen Fatal Error auf und protokolliert ihn
	 * @api
	 * @todo Prüfen, wieso catchFatalError anstatt catchError für "Parse errors" aufgerufen wird
	 */
	public static function catchFatalError() {
		$error = error_get_last();
		if( $error /* && $error['type'] == E_ERROR */ ) {
			$Log = \Brainstage\ExceptionLog::createLogEntry();
			if( $Log ) {
				$Log->title = "Fatal error (". $error['type'] .")";
				$Log->text = $error['message'];
				$Log->file = $error['file'];
				$Log->line = $error['line'];
				$Log->adopt();
				self::redirect( $Log );
			}
		}
	}


	/** Ruft benötigte Klassen auf, da diese im Fehlerfall nicht mehr per Autoload geladen werden
	 */
	private static function handshakeClasses() {
		Core::functions();
		\Brainstage\ExceptionLog::hello();
	}


	/** Leitet ggf. auf die Fehlerseite um
	 */
	private static function redirect( \Brainstage\ExceptionLog $Log=null ) {
		if( self::$_errorRedirect ) {
			$target = self::$_errorRedirect;
			if( $Log )
				$target .= '?exception='. $Log->getPrimaryKeyValue();
			Core::functions()->redirect( $target );
		}
	}


}