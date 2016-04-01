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
interface ToolsBackendInterface {

	static function buildCollapsibleSection( \rsCore\Container $Container, $title );
	static function buildLanguageSelector( $formName );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class ToolsBackend extends Tools implements ToolsBackendInterface {


/* Static methods */

	/** Baut eine Collapsible Section
	 * @param \rsCore\Container $Container
	 * @param string $title
	 * @return \rsCore\Container
	 */
	public static function buildCollapsibleSection( \rsCore\Container $Container, $title ) {
		$Section = $Container->subordinate( 'div.section' );
		$SectionTitle = 	$Section->subordinate( 'h2' );
		$SectionTitle->subordinate( 'span.chevron' )
			->subordinate( 'span.glyphicon glyphicon-chevron-right' )
			->parentSubordinate( 'span.glyphicon glyphicon-chevron-down' );
		$SectionTitle->swallow( $title );
		$Content = $Section->subordinate( 'div.collapse' );
		return $Content;
	}


	/** Baut ein Select zur Auswahl der Sprachen, die der Nutzer bearbeiten darf
	 * @param string $formName
	 * @return \rsCore\Container
	 * @api
	 * @todo Filtern nach Rechte
	 */
	public static function buildLanguageSelector( $formName=null ) {
		$usersLanguages = array_keys( \rsCore\Useragent::detectLanguages() );
		$preselectedLanguage = \rsCore\Localization::extractLanguageCode( current( $usersLanguages ) );

		$LanguageSelector = new \rsCore\Container( 'select.languageSelector.selectize' );
		if( $formName !== null )
			$LanguageSelector->addAttribute( 'name', $formName );
		foreach( self::getAllowedLanguages() as $Language ) {
				$attr = array('value' => $Language->shortCode);
				if( $Language->shortCode == $preselectedLanguage )
					$attr['selected'] = 'selected';
			$LanguageSelector->subordinate( 'option', $attr, $Language->name );
		}
		return $LanguageSelector;
	}


}