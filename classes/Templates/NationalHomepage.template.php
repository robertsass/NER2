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
interface NationalHomepageInterface {

	function extendContent( \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends NationalBase
 */
class NationalHomepage extends NationalBase implements NationalHomepageInterface {


	const ARTICLE_POSTER_SIZE = 630;
	const ARTICLE_POSTER_SIZE_RETINA = 1260;


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

	#	$this->hook( 'extendContent', 'extendMainContent' );
	}


	/** Hook zum Erweitern des Main-Containers
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContent( \rsCore\Container $Container ) {
	#	$this->buildNewsfeed( $Container );
		if( getVar('a') )
			$Article = \Nightfever\BlogArticle::getArticleById( getVar('a') );
		if( $Article )
			$this->buildArticlePage( $Container, $Article );
		else
			$this->buildBlog( $Container );
	}


	/** Baut die Detailseite eines Artikels
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildArticlePage( \rsCore\Container $Container, \Nightfever\BlogArticle $Article ) {
		$ContentArea = $Container->subordinate( 'div#content-area' );

		$ArticleContainer = $ContentArea->subordinate( 'article.clearfix.full-article' );
		self::buildArticle( $ArticleContainer, $Article );

		return $ContentArea;
	}


	/** Baut den Blog
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildBlog( \rsCore\Container $Container ) {
		$ContentArea = $Container->subordinate( 'div#content-area' );
	#	$SideArea = $Container->subordinate( 'div#side-area' );
		$articles = \Nightfever\BlogArticle::getArticlesBySite( $this->getSite(), 5, 0, true );
		if( empty( $articles ) )
			$this->buildSidebar( $ContentArea );
		else {
			foreach( $articles as $i => $Article ) {
				$ArticleContainer = $ContentArea->subordinate( 'article.clearfix' );
				if( $i == 0 ) {
				#	$ArticleContainer->addAttribute( 'class', 'row' );
					$this->buildSidebar( $ArticleContainer );
					$ArticleContainer = $ArticleContainer->subordinate( 'div.major-article.col-md-8' );
				}
				self::buildArticleTeaser( $ArticleContainer, $Article );
			}
		}
		return $ContentArea;
	}


	/** Baut die Sidebar
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildSidebar( \rsCore\Container $Container ) {
		$Sidebar = $Container->subordinate( 'div#sidebar.col-md-4' );
		$this->buildVideosWidget( $Sidebar->subordinate( 'div.widget' ) );
	}


	/** Baut den Teaser zum Blog-Artikel zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @param \Nightfever\BlogArticle $Article
	 * @return void
	 */
	public static function buildArticleTeaser( \rsCore\Container $Container, \Nightfever\BlogArticle $Article ) {
		$link = './?'. \rsCore\RequestPath::joinParameters( array_merge( rsCore()->getGlobalVariable( 'GET' ), array('a' => $Article->getPrimaryKeyValue()) ) );

		self::buildArticlePhoto( $Container, $Article );
		$Version = $Article->getVersion();
		$Header = $Container->subordinate( 'div.header' );

		if( $Version->section )
			$Header->subordinate( 'span.section', $Version->section );

		$Meta = $Header->subordinate( 'span.meta' );
		$Meta->subordinate( 'span.date', $Article->date->format( t("Y-m-d H:i", 'Date and Time: full year, without seconds') ) );

		if( $Version->title )
			$Container->subordinate( 'h1', $Version->title );
		if( $Version->subtitle )
			$Container->subordinate( 'h2', $Version->subtitle );
		if( $Version->teaser ) {
			$Paragraph = $Container->subordinate( 'p', $Version->teaser );
			$Paragraph->subordinate( 'a.readmore', array('href' => $link), t("Read more") )
				->subordinate( 'span.glyphicon glyphicon-menu-right' );
		}
		else {
			$Container->subordinate( 'a.readmore', array('href' => $link), t("Read more") )
				->subordinate( 'span.glyphicon glyphicon-menu-right' );
		}
	}


	/** Baut den gesamten Blog-Artikel zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @param \Nightfever\BlogArticle $Article
	 * @return void
	 */
	public static function buildArticle( \rsCore\Container $Container, \Nightfever\BlogArticle $Article ) {
		$link = './?'. \rsCore\RequestPath::joinParameters( array_merge( rsCore()->getGlobalVariable( 'GET' ), array('a' => $Article->getPrimaryKeyValue()) ) );

		self::buildArticlePhoto( $Container, $Article );
		$Version = $Article->getVersion();
		$Header = $Container->subordinate( 'div.header' );

		if( $Version->section )
			$Header->subordinate( 'span.section', $Version->section );

		$Meta = $Header->subordinate( 'span.meta' );
		$Meta->subordinate( 'span.date', $Article->date->format( t("Y-m-d H:i", 'Date and Time: full year, without seconds') ) );

		if( $Version->title )
			$Container->subordinate( 'h1', $Version->title );
		if( $Version->subtitle )
			$Container->subordinate( 'h2', $Version->subtitle );
		if( $Version->teaser )
			$Paragraph = $Container->subordinate( 'p.teaser', $Version->teaser );
		if( $Version->text )
			$Paragraph = $Container->subordinate( 'div.text', $Version->text );
	}


	/** Fügt das Titel-Foto des Blog-Artikels ein
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @param \Nightfever\BlogArticle $Article
	 * @return void
	 */
	public static function buildArticlePhoto( \rsCore\Container $Container, \Nightfever\BlogArticle $Article ) {
		$Galery = $Container->subordinate( 'div.galery' );
		$photos = $Article->getPhotos();
		foreach( $photos as $Photo ) {
			$Frame = $Galery->subordinate( 'div.image' );
			$Frame->subordinate( 'img', array(
				'src' => '/static/images/pixel.gif',
				'data-src' => $Photo->getURL(false, self::ARTICLE_POSTER_SIZE .'x'. self::ARTICLE_POSTER_SIZE*4),
				'data-src-retina' => $Photo->getURL(false, self::ARTICLE_POSTER_SIZE_RETINA .'x'. self::ARTICLE_POSTER_SIZE_RETINA*4)
			) );
		}
	}


	/** Baut das Video-Widget für die Sidebar
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildVideosWidget( \rsCore\Container $Container ) {
		$Container->addAttribute( 'class', 'video-widget' );
		$Container->subordinate( 'div.head > h4', t("Videos") );
		foreach( \Nightfever\SiteVideo::getVideosBySite( $this->getSite() ) as $Video ) {
			$videoUrl = @array_pop( explode( ':', $Video->url, 2 ) );
			$PlayerThumbnail = $Container->subordinate( 'a.magnific', array(
				'href' => $videoUrl,
				'title' => $Video->title,
				'data-toggle' => 'tooltip',
				'data-placement' => 'left'
			) )->subordinate( 'div.player-thumbnail' );
			$PlayerThumbnail->subordinate( 'img', array('src' => $Video->thumbnailUrl) );
			$PlayerThumbnail->subordinate( 'span.play-button > span > span.glyphicon.glyphicon-play' );
		}
	}


	/** Baut den Newsfeed zusammen
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildNewsfeed( \rsCore\Container $Container ) {
		$Feed = $Container->subordinate( 'div#newsfeed' );

		$teaser = "Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.";

		$Post = new \stdClass();
		$Post->posterImage = "media/de/newsfeed1.jpg";
		$Post->headline = "Die Vorfreude steigt";
		$Post->title = "7. internationales Nightfever-Weekend in München";
		$Post->subtitle = "Rund 200 Nightfever-Aktive in München erwartet";
		$Post->teaser = $teaser;
		$Post->sidebox = true;
		$this->buildNewsfeedArticle( $Feed, $Post );

		$Post = new \stdClass();
		$Post->posterImage = "media/de/newsfeed2.jpg";
		$Post->headline = "Nightfever Akademie";
		$Post->title = "Akademie-Tage in Mooshausen";
		$Post->subtitle = "Mit Frau Prof. Hanna Barbara Gerl-Falkovitz
(Hochschule Heiligenkreuz)";
		$Post->teaser = $teaser;
		$this->buildNewsfeedArticle( $Feed, $Post );

		$Post = new \stdClass();
		$Post->posterImage = "media/de/newsfeed3.jpg";
	#	$Post->headline = "Die Vorfreude steigt";
		$Post->title = "Nightfever beim “Kirchenschiff” der Bundesgartenschau";
	#	$Post->subtitle = "Rund 200 Nightfever-Aktive in München erwartet";
		$Post->teaser = "Die Nightfever-Städte aus Ostdeutschland gestalten vom
22. – 24. September 2015 von 10:00 – 16:00 Uhr das “Kirchenschiff” der Bundesgartenschau. Weitere Informationen erhältst du beim Nightfever-Team aus Berlin.";
		$this->buildNewsfeedArticle( $Feed, $Post );

		$Post = new \stdClass();
		$Post->posterImage = "media/de/newsfeed4.jpg";
		$Post->headline = "10 Jahre Nightfever";
		$Post->title = "Internationales Nightfever-Leiterweekend in Bonn";
	#	$Post->subtitle = "Rund 200 Nightfever-Aktive in München erwartet";
		$Post->teaser = "Vom 23.-25. Oktober 2015 wird in Bonn, wo am 29.10.2005 das erste Nightfever gefeiert wurde, das internationale Nightfever-Leiterweekend stattfinden. Die Anmeldung wird in Kürze möglich sein.";
		$this->buildNewsfeedArticle( $Feed, $Post );
	}


	/** Baut einen Newsfeed Post
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function buildNewsfeedArticle( \rsCore\Container $Container, $Post ) {
		$Article = $Container->subordinate( 'article' );
		$Article->subordinate( 'div.poster > img', array('src' => $Post->posterImage) );

		if( $Post->sidebox ) {
			$Sidebox = $Article->subordinate( 'div.sidebox' );
			$Sidebox->subordinate( 'p', "Aktuelle Videos" );
			$Sidebox->subordinate( 'div.video' );
			$Sidebox->subordinate( 'div.video' );
			$Sidebox->subordinate( 'img.calendar', array('src' => '/media/de/calendar.png') );
		}

		if( isset( $Post->headline ) )
			$Article->subordinate( 'div.headline', $Post->headline );
		if( isset( $Post->title ) )
			$Article->subordinate( 'h1', $Post->title );
		if( isset( $Post->subtitle ) )
			$Article->subordinate( 'h2', $Post->subtitle );
		if( isset( $Post->teaser ) )
			$Article->subordinate( 'p', $Post->teaser );

		$More = $Article->subordinate( 'div.affiliated' );
		if( isset( $Post->videoTitle ) ) {
			$More->subordinate( 'div' )
				->subordinate( 'span.type', "Video" )
				->parent()->swallow( $Post->videoTitle );
		}
	}


}