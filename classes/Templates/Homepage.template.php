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
interface HomepageInterface {

	function initOnepageChildTemplates();
	function buildHead( \rsCore\ProtectivePageHeadInterface $Head );
	function buildOnepage( \rsCore\Container $Container );
	function buildSidebar( \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends HomepageBase
 */
class Homepage extends Base implements HomepageInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();
	}


	/** Baut aus allen untergeordneten Dokumenten die Onepage zusammen
	 *
	 * @access public
	 * @return void
	 */
	public function initOnepageChildTemplates() {
		foreach( $this->getChildDocuments() as $Child ) {
			$ChildsTemplate = $this->instantiateTemplate( $Child );
		}
	}


	/** Hook zum Manipulieren des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
/*
	public function extendContent( \rsCore\Container $Container ) {
		$Container = $Container->subordinate( 'div.row' );
#		$Onepage = $Container->subordinate( 'div.col-md-8.clearfix#onepage' );
#		$Sidebar = $Container->subordinate( 'div.col-md-4#sidebar' );

		$Onepage->swallow( $this->getDocument()->getContent() );

		$this->initOnepageChildTemplates();

		$this->buildOnepage( $Onepage );
		$this->buildSidebar( $Sidebar );

	#	$Content->subordinate( 'h1', $this->getDocument()->getName() );
		$Content->swallow( $this->getDocument()->getContent() );


		$Sidebar->subordinate( 'div.headline', "So gelangst du zu uns" );
		$Sidebar->subordinate( 'img', array('src' => '/media/de/bonn/maps.png') );

		$Sidebar->subordinate( 'div.headline', "Unser Flyer" );
		$Sidebar->subordinate( 'img', array('src' => '/media/de/bonn/flyer.png') );

		$More = $Container->subordinate( 'section#more' );
		$More->subordinate( 'h2', "Weitere Themen" );
		$Kacheln = $More->subordinate( 'div.row' );
		$Kacheln->subordinate( 'div.four columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/more1.png') )->parent()
			->swallow( 'Internationales Jugendtreffen in Assisi' );
		$Kacheln->subordinate( 'div.four columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/more2.jpg') )->parent()
			->swallow( 'Nightfever beim Katholikentag 2014' );
		$Kacheln->subordinate( 'div.four columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/more3.png') )->parent()
			->swallow( 'Weltjugendtag in Krakau' );

		$Contact = $Container->subordinate( 'section#contact' );
		$Contact->subordinate( 'h2', "Kontakt" );
		$Spalten = $Contact->subordinate( 'div.row' );
		$Form = $Spalten->subordinate( 'div.six columns > form' );
		$Spalten->subordinate( 'div.six columns' )
			->subordinate( 'img', array('src' => '/media/de/bonn/team.png') );
		$Form->subordinate( 'p.field > input(text).text input', array('placeholder' => t("Name")) );
		$Form->subordinate( 'p.field > input(text).text input', array('placeholder' => t("E-Mail")) );
		$Form->subordinate( 'p.field > textarea.textarea input', array('placeholder' => t("Message")) );
		$Form->subordinate( 'p.field > div.medium metro rounded btn primary > a', t("Send") );
	}
*/


	/** Baut aus allen untergeordneten Dokumenten die Onepage zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildOnepage( \rsCore\Container $Container ) {
		$hooks = $this->getConstructor()->getFramework()->getHooks( 'extendOnepage' );
		foreach( $hooks as $Hook ) {
			$Section = new \rsCore\Container( 'section' );
			$Hook->call( $Section );
			if( $Section->getLastIndex() !== null )
				$Container->swallow( $Section );
		#	$Section->subordinate( 'h1', $Child->getName() );
		#	$Section->swallow( $SectionContent );
		}
	}


	/** Baut aus allen untergeordneten Dokumenten die Onepage zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildSidebar( \rsCore\Container $Container ) {
		$hooks = $this->getConstructor()->getFramework()->getHooks( 'extendSidebar' );
		foreach( $hooks as $Hook ) {
			$Widget = new \rsCore\Container( 'div.widget' );
			$Hook->call( $Widget );
			if( $Widget->getLastIndex() !== null )
				$Container->swallow( $Widget );
		}
	}


	/** Instantiiert ein Template
	 *
	 * @access private
	 * @param \Brainstage\Document $Document
	 * @return \rsCore\BaseTemplate
	 */
	private function instantiateTemplate( \Brainstage\Document $Document ) {
		$templateName = '\\'. \Autoload::TEMPLATE_NAMESPACE .'\\'. $Document->getTemplateName();
		if( $templateName != null && class_exists( $templateName ) )
			return new $templateName( $this->getConstructor(), $this->getRequest(), $Document );
		return null;
	}


}