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
 */
class CoreInit {


	const BROWSER_CACHE_FILE_MAX_AGE = 1209600;

	protected $docid;
	protected $domain;
	protected $query;
	public $Template = null;
	protected $useragent = null;

	public static $Dictionary = null;


	public function __construct() {
		if( !defined('DBNAME') )
			require_once( 'config.php' );
		static::start_session();
		$this->domain = self::getDomain();
		$this->query = self::parseRequest();
		if( self::$Dictionary == null )
			self::$Dictionary = new rsDictionary();
		if( isset($_GET['f']) )
			return $this->getFile( intval( $_GET['f'] ) );
		$this->init( $db, $head, $body );
		if( !isset($this->docid) )
			$this->docid = $this->detectRequestedPage();
		if( !defined('TEMPLATE') )
			$this->initTemplate();
	}


	protected function init( rsMysql $db=null, rsHeader $head=null, rsContainer $body=null ) {
		if($db)		$this->db = $db;
		else		$this->db = rsMysql::instance( 'tree' );
		if($head)	$this->head = $head;
		else		$this->head = new rsHeader();
		if($body)	$this->body = $body;
		else		$this->body = new rsContainer( 'body' );
	}



	public static function startCompression() {
		ob_start('ob_gzhandler');
	}


	/*	Function get_domain
		Gibt Informationen über die Domain zurück, über welche die Seite aufgerufen wird.
	*/



	protected function build( $indent=true ) {
		new rsPrinter( $this->head, $this->body, $indent, $this->docid );
	}


	protected function initTemplate() {
		if( !$this->db->table_exists( DBPREFIX .'tree' ) && intval(IS_BRAINSTAGE_DIR) == 0 )
				header( "location:./brainstage/" );	// redirect to Brainstage for auto-install
		else
			$this->Template = $this->loadTemplate();
	}


	protected static function startSession() {
		session_start();
	}


	protected static function detectRequestedPage() {
		if( !isset($_GET['i']) && defined('URL_REWRITE') && strlen( str_replace( $_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI'] ) ) > 0 )
			return static::analyseRequestPath();
		else
			return ( isset($_GET['i']) ? intval($_GET['i']) : intval(HOMEPAGE) );
	}


	protected static function analyseRequestPath() {
		$query = explode( '?', $_SERVER['REQUEST_URI'] );
		$parts = array();
		foreach( explode( '/', str_replace( $_SERVER['SCRIPT_NAME'], '', $query[0] ) ) as $part )
			if( strlen($part) > 0 )
				$parts[] = $part;
		if( count( $parts ) == 1 ) {
			$docid = static::getDocumentId( $parts[0] );
			if( $docid != null )
				return $docid;
			else
				return intval(HOMEPAGE);
		}
		else {
			reset( $parts );
			return static::getAssociatedDocument( $parts );
		}
	}


	private static function getAssociatedDocument( $parts, $parentnode=null ) {
		if( $parentnode === null )
			$parentnode = method_exists( 'Root', 'get_menunode' ) ? Root::get_menunode() : MENUNODE;
		$most_similar_doc = HOMEPAGE;
		$highest_score = 0;
		foreach( $this->getSublevelDocuments( $parentnode ) as $doc ) {
			$score = similar_text( current( $parts ), $doc['name'] );
			if( $score > $highest_score ) {
				$highest_score = $score;
				$most_similar_doc = $doc['id'];
			}
		}
		next( $parts );
		if( current( $parts ) != false )
			return $this->getAssociatedDocument( $parts, $most_similar_doc );
		return $most_similar_doc;
	}


	private static function getDocumentId( $title ) {
		return rsDatabase::instance()->getColumn( 'id', '`name` = "'. urldecode( str_replace( '_', ' ', $title ) ) .'"' );
	}


	protected function loadTemplate() {
		$template = $this->getMyTemplate();
		if( !$template || $this->db->getColumn( 'status', '`id`='. $this->docid ) == 0 ) {
			$template = $this->getMyTemplate( PAGE_NOT_FOUND );
			$_GET['i'] = PAGE_NOT_FOUND;
			$this->docid = PAGE_NOT_FOUND;
		}
		define( 'TEMPLATE', $template );
		if( !class_exists( $template ) )
			die( "Class &quot;". $template ."&quot; not found. Required for document #". $this->docid );
		$count = $this->db->getColumn( 'count', '`id` = ' . $this->docid );
		$this->db->update( array('count' => $count+1), '`id` = ' . $this->docid );
		return new $template( $this->db, $this->head, $this->body );
	}


	protected function getMyTemplate( $docid=null ) {
		if( !$docid )
			$docid = $this->docid;
		return $this->db->getColumn( 'template', '`id` = ' . $docid );
	}


	public function getLeftValue( $docid=null, rsMysql $DB=null ) {
		if( !$docid )
			$docid = $this->detectRequestedPage();
		if( !$DB )
			$DB = $this->db;
		return $DB->getColumn( 'lft', '`id` = "'. $docid .'"' );
	}


	public function getRightValue( $docid=null, rsMysql $DB=null ) {
		if( !$docid )
			$docid = $this->detectRequestedPage();
		if( !$DB )
			$DB = $this->db;
		return $DB->getColumn( 'rgt', '`id` = "'. $docid .'"' );
	}


	public function isChildOf( $docid, $DB=null ) {
		return $this->getRightValue( null, $DB ) < $this->getRightValue( $docid, $DB ) && $this->getLeftValue( null, $DB ) > $this->getLeftValue( $docid, $DB );
	}


	public function getParentDocuments( $docid, $DB=null ) {
		if( !$DB )
			$DB = $this->db;
		return $DB->get( 'SELECT *, COUNT(*)-1 AS level, ROUND((`rgt` - `lft` - 1) / 2) AS offspring FROM `%TABLE` WHERE `lft` < ' . $this->getLeftValue($docid, $DB) . ' AND `rgt` > ' . $this->getRightValue($docid, $DB) . ' GROUP BY `lft` ORDER BY `lft` DESC;' );
	}


	public function getSublevelDocuments( $rootid=null, $fields=null, rsMysql $DB=null ) {
		if( !$rootid )
			$rootid = $this->detectRequestedPage();
		if( !$DB )
			$DB = $this->db;
		if( !$fields )
			$fields = '*';
		elseif( is_object($fields) && is_subclass_of($field, 'rsMysql') )
			$DB = $fields;
		else
			$fields = '`'. implode( '`,`', $fields ) .'`';
		$docs = array();
		$lastRgt = 0;
		foreach( $DB->get( 'SELECT '. $fields .', COUNT(*)-1 AS level, ROUND((`rgt` - `lft` - 1) / 2) AS offspring FROM `%TABLE` WHERE `lft` > ' . $this->getLeftValue($rootid, $DB) . ' AND `rgt` < ' . $this->getRightValue($rootid, $DB) . ' GROUP BY `lft` ORDER BY `lft`;' ) as $leaf ) {
			if($leaf['rgt'] > $lastRgt) {
				$docs[$leaf['id']] = $leaf;
				$lastRgt = $leaf['rgt'];
			}
		}
		return $docs;
	}


	protected function get_used_classes() {
		$classes = get_declared_classes();
		$used_classes = array();
		$reached_user_defined_classes = false;
		foreach( $classes as $index => $class ) {
			if( $class == 'rsCore' )
				$reached_user_defined_classes = true;
			if( $reached_user_defined_classes )
				$used_classes[] = $class;
		}
		return $used_classes;
	}




	protected function build_doc( $docid=null ) {
		return $this->get_doc( $docid );
	}


	protected function get_doc( $docid=null ) {
		if(!$docid)
			$docid = $this->docid;
		return new rsPage( $docid, $this->db );
	}


	public function db( $table ) {
		return rsMysql::instance( $table );
	}


	public static function redirect( $url, $die=true ) {
		header( 'location: '. $url );
		if( $die )
			die();
	}


	public static function decode_size( $bytes ) {
	    $types = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	    for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
	    return( round( $bytes, 2 ) . " " . $types[$i] );
	}


	public static function readable_dateDiff( $first_date, $second_date=null ) {
		if( $second_date == null )
			$second_date = time();
		$first_date = new rsCalendar( $first_date );
		$second_date = new rsCalendar( $second_date );

		$diff_secs = round( ($second_date->timestamp() - $first_date->timestamp()), 0 );
		$diff_mins = round( $diff_secs /60, 0 );
		$diff_hours = round( $diff_mins /60, 0 );
		$diff_days = round( ($second_date->day_beginning() - $first_date->day_beginning()) /60/60/24, 0 );

		if( $diff_secs <= 45 ) {
			$readable = rsDictionary::t("%n seconds ago");
			$readable = str_replace( '%n', $diff_secs, $readable );
		}
		elseif( $diff_mins <= 1 ) {
			$readable = rsDictionary::t("one minute ago");
		}
		elseif( $diff_mins < 60 ) {
			$readable = rsDictionary::t("%n minutes ago");
			$readable = str_replace( '%n', $diff_mins, $readable );
		}
		elseif( $diff_hours < 12 ) {
			$readable = rsDictionary::t("%n hours ago");
			$readable = str_replace( '%n', $diff_hours, $readable );
		}
		elseif( $diff_days == 0 )
			$readable = rsDictionary::t("today");
		elseif( $diff_days == 1 )
			$readable = rsDictionary::t("yesterday");
#		elseif( $diff_days == 2 )
#			$readable = rsDictionary::t("the day before yesterday");
		else {
			$readable = rsDictionary::t("%n days ago");
			$readable = str_replace( '%n', $diff_days, $readable );
		}

		return $readable;
	}


	public static function convert_objectToArray( $object ) {
		if( is_array($object) )
			return $object;
		if( !is_object($object) )
			return null;

		$array = array();

		foreach( $object as $key => $value )
			$array[ $key ] = $value;

		return $array;
	}


}