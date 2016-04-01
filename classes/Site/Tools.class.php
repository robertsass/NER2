<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Site;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface ToolsInterface {

	static function convertDateformatToMomentjsFormat( $format );
	static function serveCalendarFile( $events );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class Tools extends \rsCore\CoreClass implements ToolsInterface {


	/** Gibt alle Languages zurück, auf die der Nutzer Zugriff hat
	 * @param User $User
	 * @return array Gibt ein Array von Language-Objekten zurück
	 * @api
	 */
	public static function getAllowedLanguages( \Brainstage\User $User=null ) {
		return \Brainstage\Plugins\Translation::getLanguagesAllowedToEdit();
	}


	/** Konvertiert ein PHP-Datumsformat in ein moment.js-Format
	 * @return string
	 */
	public static function convertDateformatToMomentjsFormat( $format ) {
		$format = \rsCore\Calendar::localizeFormat( $format );
		$format = str_replace( 'd', 'DD', $format );
		$format = str_replace( 'm', 'MM', $format );
		$format = str_replace( 'y', 'YY', $format );
		$format = str_replace( 'Y', 'YYYY', $format );
		$format = str_replace( 'H', 'HH', $format );
		$format = str_replace( 'i', 'mm', $format );
		$format = str_replace( 's', 'ss', $format );
		return $format;
	}


	/** Gibt die Veranstaltungen als iCal-Datei aus
	 * @param array $events
	 * @return string
	 * @api
	 */
	public static function serveCalendarFile( $events, $title=null, $author=null ) {
		if( !is_array( $events ) || empty( $events ) )
			return null;
		if( $title && $author === null )
			$author = $title;
		$Calendar = new Calendar();
		$Calendar->setTitle( $title );
		$Calendar->setAuthor( $author );
		foreach( $events as $Event ) {
			$Location = $Event->getLocation();
			$CalendarEvent = new CalendarEvent( array(
				'uid' => $Event->getPrimaryKeyValue(),
				'start' => $Event->start,
				'end' => $Event->end,
				'summary' => $Event->getTitle(),
				'description' => $Event->getDescription(),
				'location' => $Location ? $Location->name : ''
			) );
			$Calendar->addEvent( $CalendarEvent );
		}
		$Calendar->generateDownload();
	}


}