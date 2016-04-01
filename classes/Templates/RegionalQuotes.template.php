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
interface RegionalQuotesInterface {

	public static function buildQuotesList( Base $Template, \rsCore\Container $Container );

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 *
 * @extends Base
 */
class RegionalQuotes extends Base implements RegionalQuotesInterface {


	/** Dient als Konstruktor
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->hook( 'extendContentArea' );
		$this->hook( 'extendOnepage' );
	}


	/** Hook zum Erweitern des Contents
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendContentArea( \rsCore\Container $Container ) {
		return self::buildQuotesList( $this, $Container );
	}


	/** Hook zum Erweitern der Homepage durch eine Onepage-Sektion
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public function extendOnepage( \rsCore\Container $Container ) {
		$this->buildOnepageSection( $Container );
	}


	/** Baut die Zitat-Liste
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return void
	 */
	public static function buildQuotesList( Base $Template, \rsCore\Container $Container ) {
		$Group = $Container->subordinate( 'div.quotes > div.quote-group' );
		foreach( $Template->getCity()->getQuotes() as $Quote ) {
			$Entry = $Group->subordinate( 'div.quote' );
			$Entry->subordinate( 'div.text', $Quote->text );
			$Meta = $Entry->subordinate( 'div.meta' );
			$Meta->subordinate( 'span.author', $Quote->author );
			$Meta->subordinate( 'span.age', '('. $Quote->age .')' );
		}
	}


	/** Baut die Onepage-Sektion
	 *
	 * @access public
	 * @param \rsCore\Container $Container
	 * @return \rsCore\Container $Container
	 */
	public function buildOnepageSection( \rsCore\Container $Container ) {
		$Quote = $this->getRandomQuote();
		$Entry = $Container->subordinate( 'div.quote' );
		$Entry->subordinate( 'div.text', $Quote->text );
		$Meta = $Entry->subordinate( 'div.meta' );
		$Meta->subordinate( 'span.author', $Quote->author );
		$Meta->subordinate( 'span.age', '('. $Quote->age .')' );
	}


}