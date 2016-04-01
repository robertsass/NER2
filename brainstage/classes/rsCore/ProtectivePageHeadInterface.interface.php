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
interface ProtectivePageHeadInterface {

	function getPagetitle();
	function setPagetitle( $title );

	function getStylesheets();
	function linkStylesheet( $stylesheetPath, $media );

	function getScripts();
	function linkScript( $scriptPath );

	function getMetas();
	function addMetaName( $name, $content );
	function addMetaHttpEquiv( $httpEquiv, $content );

	function getOthers();
	function addOther( $snippetOrContainerInstance );

	function getLinks();
	function addLink( $rel, $href, $type, $language );

}