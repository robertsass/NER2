<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Brainstage\Templates;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface BaseInterface {

	function init();
	function build();

	function buildHead( \rsCore\ProtectivePageHeadInterface $Head );
	function buildBody( \rsCore\Container $Body );
	function buildNavigator( \rsCore\Container $Navigator );
	function buildMainContent( \rsCore\Container $Container );

	static function buildCollapsibleSection( \rsCore\Container $Container, $title );

}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\HTMLTemplate
 */
class Base extends \rsCore\HTMLTemplate implements BaseInterface {


	private $_pluginContainer = array();


/* Private methods */

	protected function getHooks( $event=null ) {
		return $this->getConstructor()->getFramework()->getHooks( $event );
	}


	protected function getHookIdentifier( $Hook, $fullIdentifier=null, $lowercase=false ) {
		$HookedObject = $Hook->getObject();
		if( $fullIdentifier === null ) {
			$isBrainstagePlugin = self::isNativeBrainstagePlugin( $HookedObject );
			$fullIdentifier = !$isBrainstagePlugin;
		}
		$identifier = \Brainstage\Brainstage::encodeIdentifier( $HookedObject, $fullIdentifier );
		return $lowercase ? strtolower( $identifier ) : $identifier;
	}


	protected static function isNativeBrainstagePlugin( \rsCore\Reflectable $Object ) {
		return strpos( $Object->getReflection()->getNamespaceName(), 'Brainstage' ) !== false;
	}


/* Public methods */

	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->unhook( 'buildHead' );
		$this->unhook( 'buildBody' );

		$this->hook( 'buildNavigator' );
		$this->hook( 'buildMainContent' );

		$this->callHooks( 'initTemplate' );
	}


	/** Hook zum Manipulieren des HTML-Bodys
	 *
	 * @access public
	 * @return string
	 */
	public function build() {
		$this->buildHead( $this->getPageHead() );
		$this->buildBody( $this->getPageBody() );

		$this->callHooks( 'beforeBuild' );

		// Build HTML
		$Page = new \rsCore\Container( 'html' );
		$Page->swallow( $this->getPageHead()->build() );
		$Page->swallow( $this->getPageBody() );

		// Send Header
		header( 'Content-Type: text/html; charset=utf-8' );

		// Return HTML
		return $Page->summarize();
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @return void
	 * @todo Merge as many resources as possible to one file
	 */
	public function buildHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		parent::buildHead( $Head );
		
		$Head->setPageTitle( \Brainstage\Brainstage::translate("Brainstage 2") );
		
		$Head->addMetaName( 'language', \rsCore\Localization::getLanguage() );
		$Head->addMetaName( 'viewport', 'width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no' );

		$Head->linkStylesheet( 'static/bootstrap/css/bootstrap.min.css' );
		$Head->linkStylesheet( 'static/bootstrap/css/bootstrap-theme.min.css' );
		$Head->linkStylesheet( 'static/css/bootstrap-datetimepicker.min.css' );
		$Head->linkStylesheet( 'static/css/selectize.css' );
		$Head->linkStylesheet( 'static/css/selectize.default.css' );
		$Head->linkStylesheet( 'static/css/grid.css' );
		$Head->linkStylesheet( 'static/fonts/brainicons/brainicons.css' );
		$Head->linkStylesheet( 'static/css/main.css' );

		$Head->linkScript( 'static/js/jquery.js' );
		$Head->linkScript( 'static/js/jquery-ui.min.js' );
		$Head->linkScript( 'static/js/jquery.finger.min.js' );
		$Head->linkScript( 'static/js/dropzone.js' );
		$Head->linkScript( 'static/bootstrap/js/bootstrap.min.js' );
		$Head->linkScript( 'static/js/bootbox.min.js' );
		$Head->linkScript( 'static/js/selectize.min.js' );
		$Head->linkScript( 'static/js/moment-with-locales.min.js' );
		$Head->linkScript( 'static/js/bootstrap-datetimepicker.min.js' );
		$Head->linkScript( 'static/js/extensions.js' );
		$Head->linkScript( 'static/js/main.js' );

		$this->callHooks( 'buildHead', array( $this->getPageHead()->getProtectiveInstance() ) );
	}


	/** Hook zum Manipulieren des HTML-Bodys
	 *
	 * @access public
	 * @return void
	 */
	final public function buildBody( \rsCore\Container $Body ) {
		$Body->addAttribute( 'class', 'colset' );
		$Navigator = $Body->subordinate( 'div#navigator.col3 > nav' );
		$Main = $Body->subordinate( 'div#main.col-3' );

		$this->buildTopBar( $Main );
		$this->buildMainContent( $Main );
		$this->buildNavigator( $Navigator );
	}


	/** Erstellt den Navigator
	 *
	 * @access public
	 * @return void
	 */
	final public function buildNavigator( \rsCore\Container $Navigator ) {
		$menuItems = array();
		$hooks = $this->getHooks( 'getNavigatorItem' );
		if( !empty( $hooks ) ) {
			foreach( $hooks as $Hook ) {
				$menuItemLabel = $Hook->call();
				if( is_string( $menuItemLabel ) ) {
					$simpleIdentifier = $this->getHookIdentifier( $Hook );
					$identifier = $Hook->getObject()->getIdentifier();
					$menuItem = array('label' => $menuItemLabel, 'simpleIdentifier' => $simpleIdentifier, 'identifier' => $identifier);

					if( isset( $this->_pluginContainer[ $identifier ]['view'] ) ) {
						if( self::isNativeBrainstagePlugin( $Hook->getObject() ) )
							$menuItems['native'][ $identifier ] = $menuItem;
						else
							$menuItems['plugins'][ $identifier ] = $menuItem;
						$this->_pluginContainer[ $identifier ]['navigator'] = $menuItem;
					}
				}
			}
			if( array_key_exists( 'plugins', $menuItems ) )
				asort( $menuItems['plugins'] );
		}

		$siteName = \Brainstage\Brainstage::getSiteName();
		$domainName = \rsCore\Core::core()->getRequestPath()->domain->orig;
		$Navigator->subordinate( 'h1 > a', array('href' => '../', 'target' => 'brainstage_site'), ($siteName != '' ? $siteName : $domainName) );
		$Navigator->subordinate( 'span', \Brainstage\Brainstage::translate("Navigator") );
		$Menu = $Navigator->subordinate( 'ul#menu' );
		if( array_key_exists( 'native', $menuItems ) )
			foreach( $menuItems['native'] as $identifier => $menuItem )
				self::buildNavigatorItem( $Menu, $menuItem );

		if( array_key_exists( 'plugins', $menuItems ) ) {
			$Navigator->subordinate( 'span', \Brainstage\Brainstage::translate("Plugins") );
			$Menu = $Navigator->subordinate( 'ul#menu' );
		#	$Menu->subordinate( 'li.separator' );
			foreach( $menuItems['plugins'] as $identifier => $menuItem )
				self::buildNavigatorItem( $Menu, $menuItem );
		}

		$Navigator->subordinate( 'span', \Brainstage\Brainstage::translate("Open files") );
		$Tabs = $Navigator->subordinate( 'ul#open_tabs' );

		$User = \rsCore\Auth::getUser();
		$NavigatorFooter = $Navigator->parent()->subordinate( 'div.footer' );
		$NavigatorFooter->subordinate( 'span', $User->name );
		$NavigatorFooter->subordinate( 'p > a.btn btn-danger btn-xs', array('href' => '?logout'), \Brainstage\Brainstage::translate("Logout") );
	}


	/** Erstellt ein Navigator-Item
	 *
	 * @access public
	 * @return void
	 */
	final public static function buildNavigatorItem( \rsCore\Container $Container, $menuItem ) {
		$ItemContainer = $Container->subordinate( 'li', array('data-identifier' => $menuItem['identifier']) );
		$Link = $ItemContainer->subordinate( 'a', array('href' => '#'. $menuItem['simpleIdentifier']), $menuItem['label'] );
		$Badge = $Link->subordinate( 'span.badge' );
	}


	/** Baut die Topbar auf
	 *
	 * @access public
	 * @return void
	 */
	final public function buildTopBar( \rsCore\Container $Container ) {
		$Bar = $Container->subordinate( 'div#topbar.colset' );
		$Left = $Bar->subordinate( 'div.col2.left' );
		$Center = $Bar->subordinate( 'div.col-4.center' );
		$Right = $Bar->subordinate( 'div.col2.right' );
		
		$Center->subordinate( 'div.title', \Brainstage\Brainstage::translate("Brainstage 2") );
		$Left->subordinate( 'button.btn.btn-link.open-offcanvas > span.icon.icon-menu' );
	}


	/** Baut den Main-Container auf
	 *
	 * @access public
	 * @return void
	 */
	final public function buildMainContent( \rsCore\Container $Container ) {
		foreach( $this->getHooks( 'buildBody' ) as $Hook ) {
			$identifier = $this->getHookIdentifier( $Hook );
			$fullIdentifier = $Hook->getObject()->getIdentifier();
			$HookContainer = new \rsCore\Container( 'div', array(
				'id' => $identifier,
				'data-identifier' => $fullIdentifier
			) );

			try {
				$Hook->call( $HookContainer );
			}
			catch( \Exception $Exception ) {
				self::buildExceptionWarning( $HookContainer->clear(), $Exception );
			}

			$this->_pluginContainer[ $fullIdentifier ]['view'] = $HookContainer;
			$Container->swallow( $HookContainer );
		}
	}


	/** Baut eine Exception-Meldung
	 * @param \rsCore\Container $Container
	 * @param \Exception $Exception
	 * @return \rsCore\Container
	 */
	final public static function buildExceptionWarning( \rsCore\Container $Container, \Exception $Exception ) {
		$Box = $Container->subordinate( 'div.alert.alert-danger' );
	#	$Box = $Container->subordinate( 'div.jumbotron.bg-danger' );
		$Box->subordinate( 'h1', \Brainstage\Brainstage::translate("Error in plugin") );
		$Box->subordinate( 'p > button(submit).btn btn-danger', \Brainstage\Brainstage::translate("Deactivate plugin") );

/*
		$ExceptionNotice = $Container->subordinate( 'div.panel.panel-default' );
		$ExceptionNotice->subordinate( 'div.panel-heading > h2', $Exception->getMessage() );
		$ExceptionNotice->subordinate( 'div.panel-body > samp', $Exception->getTraceAsString() );
*/

		$ExceptionNotice = $Container->subordinate( 'div.panel.panel-default' );
		$ExceptionNotice->subordinate( 'div.panel-heading > h2', $Exception->getMessage() );
		$Table = $ExceptionNotice->subordinate( 'div.panel-body > table.table table"' );
		$TableHead = $Table->subordinate( 'thead > tr > th' );
		$TableHead->swallow( $Exception->getFile() .' ('. $Exception->getLine() .')' );
		$Table = $Table->subordinate( 'tbody' );
		foreach( explode( "\n", $Exception->getTraceAsString() ) as $line )
			$Table->subordinate( 'tr > td', $line );

		return $Container;
	}


	/** Baut eine Collapsible Section
	 * @param \rsCore\Container $Container
	 * @param string $title
	 * @param boolean $expanded
	 * @return \rsCore\Container
	 */
	final public static function buildCollapsibleSection( \rsCore\Container $Container, $title, $expanded=false ) {
		$Section = $Container->subordinate( 'div.section' . ($expanded ? '.expanded' : '') );
		$SectionTitle = 	$Section->subordinate( 'h2' );
		$SectionTitle->subordinate( 'span.chevron' )
			->subordinate( 'span.glyphicon glyphicon-chevron-right' )
			->parentSubordinate( 'span.glyphicon glyphicon-chevron-down' );
		$SectionTitle->swallow( $title );
		$Content = $Section->subordinate( 'div.collapse' . ($expanded ? '.in' : '') );
		return $Content;
	}


}