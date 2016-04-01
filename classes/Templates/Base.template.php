<?php
/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @link http://www.brainedia.com
 * @link http://robertsass.me
 */

namespace Templates;


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 * @internal
 */
interface BaseInterface {

    function getLanguage();

    function getChildDocuments( $onlyPublicAccessible );
    function getChildUrlByTemplate( $templateName );
    function getContactEmailAddress();
    function getLatestQuote();
    function getRandomQuote();

    function buildHead( \rsCore\ProtectivePageHeadInterface $Head );
    function buildBody( \rsCore\Container $Container );
    function buildTop( \rsCore\Container $Container );
    function buildContent( \rsCore\Container $Container );
    function buildFooter( \rsCore\Container $Container );
    function buildMenu( \rsCore\Container $Container );
    function buildLanguageSwitch( \rsCore\Container $Container );
    function buildMainMenu( \rsCore\Container $Container );
    function buildModals( \rsCore\Container $Container );
    function insertTracking( \rsCore\Container $Container );

}


/** BaseTemplate class.
 *
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends \rsCore\HTMLTemplate
 */
class Base extends \rsCore\HTML5Template implements BaseInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

	#	\Brainstage\User::addUser( 'rs@brainedia.de', 'orange' );
	#	var_dump( isLoggedin() );
	#	\rsCore\Auth::login( 'rs@brainedia.de', 'orange' );
	#	var_dump( user()->getRights() );

		$this->hook( 'buildSidebar' );
		$this->hook( 'buildMainContent' );
		$this->hook( 'extendContent' );
	}


	/** Gibt die momentan ausgewählte Sprache zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getLanguage() {
		$languageCode = \rsCore\Localization::getLanguage();
		return \Brainstage\Language::getLanguageByShortCode( $languageCode );
	}


	/** Gibt die Kontaktadresse dieser Site zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getContactEmailAddress() {
	#	return $this->getCountry()->getSetting( 'contact-mail' );
	}


	/** Gibt die Kind-Dokumente ersten Levels dieser Site zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getChildDocuments( $onlyPublicAccessible=true ) {
		if( !$onlyPublicAccessible )
			return $this->getDocument()->getChildren();

		$children = array();
		foreach( $this->getDocument()->getChildren() as $Child ) {
			if( $Child->accessibility != 'public' )
				continue;
			$children[] = $Child;
		}
		return $children;
	}


	/** Gibt das Kind-Dokument mit dem gegebenen Template zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getChildByTemplate( $templateName, $onlyPublicAccessible=false ) {
		foreach( $this->getChildDocuments( $onlyPublicAccessible ) as $Child ) {
			if( $Child->getTemplateName() == $templateName )
				return $Child;
		}
		return null;
	}


	/** Gibt die URL zu einem Kind-Dokument des gegebenen Templates zurück
	 *
	 * @access public
	 * @return string
	 */
	public function getChildUrlByTemplate( $templateName, $onlyPublicAccessible=false ) {
		$Child = $this->getChildByTemplate( $templateName, $onlyPublicAccessible );
		if( $Child )
			return $Child->getComposedUrl();
		return null;
	}


	/** Gibt das neueste Zitat zurück
	 *
	 * @access public
	 * @return \Nightfever\Quote
	 */
	public function getLatestQuote() {
		$quotes = \Site\Quote::getQuotes( 1, 0 );
		return is_array( $quotes ) ? current( $quotes ) : null;
	}


	/** Gibt ein zufälliges Zitat zurück
	 *
	 * @access public
	 * @return \Nightfever\Quote
	 */
	public function getRandomQuote() {
		$count = \Site\Quote::count( '`siteId`="'. intval( $this->getSite()->getPrimaryKeyValue() ) .'"' );
		$random = mt_rand( 0, $count-1 );
		$quotes = \Site\Quote::getQuotes( 1, $random );
		return is_array( $quotes ) ? current( $quotes ) : null;
	}


	/** Konfiguriert den HTML-Head
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 * @todo Merge as many resources as possible to one file
	 */
	public function buildHead( \rsCore\ProtectivePageHeadInterface $Head ) {
		parent::buildHead( $Head );
		$Head->addMetaName( 'language', \rsCore\Localization::getLanguage() );

		$Head->linkStylesheet( '/static/bootstrap/css/bootstrap.min.css' );
		$Head->linkStylesheet( '/static/bootstrap/css/bootstrap-theme.min.css' );
/*
		$Head->linkStylesheet( '/static/css/selectize.css' );
		$Head->linkStylesheet( '/static/css/selectize.nf.css' );
		$Head->linkStylesheet( '/static/css/magnific-popup.css' );
	#	$Head->linkStylesheet( '/static/css/nighticon.css' );
		$Head->linkStylesheet( '/static/fonts/StudioScript/StudioScript.css' );
		$Head->linkStylesheet( '/static/css/animate.min.css' );
		$Head->linkStylesheet( '/static/css/owl.carousel.css' );
		$Head->linkStylesheet( '/static/css/common.css' );
*/

/*
		$Head->linkScript( '/static/js/jquery-2.1.1.min.js' );
		$Head->linkScript( '/static/js/modernizr-2.6.2.min.js' );
		$Head->linkScript( '/static/bootstrap/js/bootstrap.min.js' );
		$Head->linkScript( '/static/js/owl.carousel.min.js' );
		$Head->linkScript( '/static/js/jquery.unveil.js' );
		$Head->linkScript( '/static/js/jquery.magnific-popup.min.js' );
		$Head->linkScript( '/static/js/selectize.min.js' );
		$Head->linkScript( '/static/js/main.js' );
	#	$Head->linkScript( '//cdn.sublimevideo.net/js/zrxgf3rt.js' );
*/

		$languageCode = \rsCore\Localization::getLanguage();
		$domainBase = rsCore()->getRequestPath()->domain->domainbase;
/*
		$Head->addLink( 'alternate', 'http://'. $languageCode .'.'. $domainBase, null, $languageCode );
		$Head->addLink( 'alternate', 'http://www.'. $domainBase, null, 'x-default' );
*/

		$this->callHooks( 'extendHead', array( $Head->getProtectiveInstance() ) );
	}


	/** Hook zum Manipulieren des HTML-Headers
	 *
	 * @access public
	 * @param \rsCore\PageHead $Head
	 * @return void
	 */
	public function extendHead( \rsCore\ProtectivePageHeadInterface $Head ) {
	}


	/** Baut den HTML-Body
	 *
	 * @access public
	 * @param \rsCore\Container $Body
	 * @return void
	 */
	public function buildBody( \rsCore\Container $Body ) {
		$BodyContainer = $Body->subordinate( 'div#body' );

		$Main = $BodyContainer->subordinate( 'div#main' );
		$Content = $Main->subordinate( 'div#content > div.inner' );

		$this->buildContent( $Content );
	}


	/** Baut die fixierte Topbar zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Top
	 * @return \rsCore\Container
	 */
	public function buildTop( \rsCore\Container $Top ) {
		$Logo = $Top->subordinate( 'a', array('href' => '/', 'title' => t("Homepage")) )->subordinate( 'span#logo', "Villa Palma Florida" );
	#	$this->buildLanguageSwitch( $Top );
		$this->callHooks( 'extendTop', $Top );
		return $Top;
	}


	/** Baut den Content zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Body
	 * @return \rsCore\Container
	 */
	public function buildContent( \rsCore\Container $Content ) {
		$this->callHooks( 'extendContent', $Content );
		return $Content;
	}


	/** Baut den Footer zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildFooter( \rsCore\Container $Container ) {
	#	$Container->subordinate( 'a#backtop.nighticon-up-open-big' );
	#	$Container->subordinate( 'a#scrolldown.nighticon-down-open-big' );
		$Footer = $Container->subordinate( 'footer > div.inner' );
		return $Footer;
	}


	/** Baut das Menü zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildMenu( \rsCore\Container $Container ) {
		$MenuContainer = $Container->subordinate( 'div#menu' );
		$Menu = $MenuContainer->subordinate( 'nav' );
		$MenuList = $this->buildMainMenu( $Menu );
		$this->callHooks( 'extendMenu', $Menu, $MenuList );
	}


	/** Baut die Sprachauswahl
	 *
	 * @access public
	 * @param \rsCore\Container $Top
	 * @return void
	 */
	public function buildLanguageSwitch( \rsCore\Container $Top ) {
		$languages = \Site\Tools::getAllowedLanguages();
		if( $languages ) {
			$LanguageList = $Top->subordinate( 'ul#languages' );
			foreach( $languages as $Language ) {
				$imagePath = '/static/images/flags-flat/32/'. strtoupper( $Language->getRegionCode() ) .'.png';
				$_GET['language'] = $Language->shortCode;
				$href = '/?'. \rsCore\RequestPath::joinParameters( $_GET );
				$LanguageList->subordinate( 'li > a', array(
						'href' => $href,
						'title' => $Language->name
					) )->subordinate( 'img', array('src' => $imagePath, 'alt' => $Language->name) );
			}
		}
	}


	/** Füllt das Menü
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return \rsCore\Container
	 */
	public function buildMainMenu( \rsCore\Container $Menu ) {
	#	$Menu->subordinate( 'div.title', t("Nightfever") );
		$MenuIcons = $Menu->parent()->subordinate( 'div.menu-icons' );
		$MenuIcons->subordinate( 'span.icon-menu' );
	#	$MenuIcons->subordinate( 'span.icon-chat' );
	#	$MenuIcons->subordinate( 'span.icon-phone' );

		$this->buildContactButtons( $Menu );

		$MenuList = $Menu->subordinate( 'ul#main-menu' );
		$children = \Brainstage\Document::getDocumentById( MENU_NODE, \rsCore\Localization::getLanguage() )->getChildren();
		foreach( $children as $i => $Child ) {
			if( $Child->accessibility != 'public' )
				continue;
			$isSelected = $Child->getLeftValue() <= $this->getDocument()->getLeftValue() && $Child->getRightValue() >= $this->getDocument()->getRightValue();
			$Link = $MenuList->subordinate( 'li'. ($isSelected ? '.selected' : '') .' > a', $Child->getName() );
			$Link->addAttribute( 'href', '/'. $Child->getComposedUrl() );
		}

		$this->buildContactButtons( $MenuList->subordinate( 'li' ) );

		return $MenuList;
	}


	/** Baut die Kontakt-Buttons
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return \rsCore\Container
	 */
	public function buildContactButtons( \rsCore\Container $Container ) {
		$attr = array(
			'data-container' => 'body',
			'data-toggle' => 'popover',
			'data-placement' => 'bottom',
			'data-content' => 'Vivamus sagittis lacus vel augue laoreet rutrum faucibus.'
		);
		$chatAttr = array_merge( $attr, array(
		) );
		$phoneAttr = array(
			'data-toggle' => 'modal',
			'data-target' => '#callModal'
		);

		$ButtonGroup = $Container->subordinate( 'div.contact-buttons > div.btn-group' );
		$ButtonGroup->subordinate( 'a.btn.btn-success', array('href' => '/anfrage') )
			->subordinate( 'span', "Anfragen" );
		$ButtonGroup->subordinate( 'a.btn.btn-success', array('href' => '/anfrage') )
			->subordinate( 'span.icon-chat' );
		$ButtonGroup->subordinate( 'button.btn.btn-success', $phoneAttr )->subordinate( 'span.icon-phone' );
	}


	/** Baut den Panorama-Slider
	 *
	 * @access public
	 * @param \rsCore\Container $Top
	 * @return void
	 */
	public function buildPanoramaSlider( \rsCore\Container $Container ) {
		$Album = \Site\PhotoAlbum::getAlbumById( self::PANORAMA_SLIDER_PHOTO_ALBUM );

		$Banner = $Container->subordinate( 'div#panorama' );
		$Slider = $Banner->subordinate( 'div.slider' );

		$photos = $Album->getPhotos();
		shuffle( $photos );
		foreach( $photos as $Photo ) {
			$File = $Photo->getFile();
			if( $File ) {
				$url = $File->getURL( false, '2000x1500' );
				$Slider->subordinate( 'div > img', array('src' => '/'. $url) );
			}
		}

	#	$BannerContent = $Banner->subordinate( 'div.inner > div.content' );

/*
		$LatestQuote = $this->getLatestQuote();
		$RandomQuote = $this->getRandomQuote();
		$Quote = $RandomQuote;

		if( $Quote ) {
			$BannerContent->subordinate( 'p', '&laquo;'. $Quote->text .'&raquo;' );
			$BannerContent->subordinate( 'p', $Quote->author .' ('. $Quote->age .')' );
		}
*/
	}


	/** Hook zum Manipulieren des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContent( \rsCore\Container $Container ) {
		$ContentArea = $Container->subordinate( 'div#content-area' );
		$this->buildPageContent( $ContentArea, true );
		$this->callHooks( 'extendContentArea', $ContentArea );
	}


	/** Baut den Standard Content
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildPageContent( \rsCore\Container $Container, $printTitle=true ) {
		if( $printTitle )
			$Container->subordinate( 'h1', $this->getDocument()->getName() );
		$Container->swallow( $this->getDocument()->getContent() );
		return $Container;
	}


	/** Baut die Modals zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildModals( \rsCore\Container $Container ) {
		$PhoneNumberModal = $Container->subordinate( 'div#callModal.modal.fade > div.modal-dialog.modal-lg > div.modal-content' );
		$PhoneNumberModal->subordinate( 'div.modal-header' )
			->subordinate( 'button(button).close', array('data-dismiss' => 'modal') )->subordinate( 'span', '&times;' )
			->parent()->parent()
			->subordinate( 'h2.modal-title', "Rufen Sie uns gerne an!" );
		$PhoneNumberModal->subordinate( 'div.modal-body > a', array('href' => 'tel://+49 123 45 67 89'), '+49 123 45 67 89' );
	}


	/** Fügt den Tracking-Code ein
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function insertTracking( \rsCore\Container $Container ) {
		$piwikSiteId = constant( 'PIWIK_SITE_ID' );
		$piwikHost = constant( 'PIWIK_HOST' );

		$js = '
		var _paq = _paq || [];
		_paq.push(["trackPageView"]);
		_paq.push(["enableLinkTracking"]);
		(function() {
		var u="//'. $piwikHost .'/";
		_paq.push(["setTrackerUrl", u+"piwik.php"]);
		_paq.push(["setSiteId", '. $piwikSiteId .']);
		var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
		g.type="text/javascript";
		g.async=true;
		g.defer=true;
		g.src=u+"piwik.js";
		s.parentNode.insertBefore(g,s);
		})();
		';
		$Container->subordinate( 'script', array('type' => 'text/javascript'), $js );
		$Container->subordinate( 'noscript > img', array('src' => '//'. $piwikHost .'?idsite='. $piwikSiteId, 'alt' => '', 'style' => 'border: 0') );

		// Insert ClickHeat-Tracking
		$Container->subordinate( 'script', array('type' => 'text/javascript', 'src' => '//'. $piwikHost .'/plugins/ClickHeat/libs/js/clickheat.js') );
		$js = '
		clickHeatSite = 1;
		clickHeatGroup = (document.title == "" ? "-none-" : encodeURIComponent(document.title));
		clickHeatServer = "//'. $piwikHost .'/plugins/ClickHeat/libs/click.php";
		initClickHeat();
		';
		$Container->subordinate( 'script', array('type' => 'text/javascript'), $js );
	}


}
