<?php	/* rsDictionary 2.7 */

class rsDictionary {


	private $rsDictionaryObject = null;


	public function __construct( $language=null, $dynamic_update=true ) {
		$this->rsDictionaryObject = new rsDatabaseBasedDictionary( $language, $dynamic_update );
	}


	public function __get( $p1 ) {
		return $this->rsDictionaryObject->__get( $p1 );
	}


	public function __call( $method, $params ) {
		return call_user_func_array( array( $this->rsDictionaryObject, $method ), $params );
	}


	public function __set( $p1, $p2 ) {
		return $this->rsDictionaryObject->__set( $p1, $p2 );
	}


	public static function t( $key ) {
		if( rsCore::$Dictionary === null )
			return $key;
		return rsCore::$Dictionary->get( $key );
	}


}


class rsDatabaseBasedDictionary {


	private $database = null;
	private $dictionary = null;
	private $language = null;
	private $dynamic_update = false;
	private $changes = null;

	private $languages = null;


	public function __construct( $language=null, $dynamic_update=true ) {
		$this->database = rsMysql::instance( 'dictionary' );
		if( !$this->database->table_exists() )
			$this->setup_dictionary_table();
		$this->dynamic_update = $dynamic_update;
		if( $language )
			$this->set_language( $language );
	}


	public function __destruct() {
	#	if( $this->dynamic_update )
	#		$this->update_library();
	}


	protected function setup_dictionary_table() {
		$sql = 'CREATE TABLE `'. DBPREFIX .'dictionary` (`id` int(10) unsigned NOT NULL auto_increment, `language` varchar(16) NOT NULL, `key` text NOT NULL, `value` text, PRIMARY KEY  (`id`), KEY `language` (`language`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		return rsMysql::instance( 'tree' )->execute( $sql );
	}


	protected static function init_dictionary( $language ) {
		$pairs = rsMysql::instance( 'dictionary' )->getAll( '`language`="'. $language .'"' );
		$Dictionary = new stdClass();
		foreach( $pairs as $pair ) {
			$key = $pair['key'];
			$Dictionary->{ $key } = $pair['value'];
		}
		return $Dictionary;
	}


	public function get_dictionary() {
		return $this->dictionary;
	}


	private function update_dictionary() {
		foreach( $this->changes as $changed_key ) {
			$value = $this->dictionary->{ $changed_key };
			$this->set( $key, $value );
		}
		$this->changes = array();
	}


	public function adopt() {
		$this->update_dictionary();
		return $this;
	}


	public function set_language( $language ) {
		$this->dictionary = self::init_dictionary( $language );
		$this->language = $language;
		$this->changes = array();
	}



	public function get_language() {
		return $this->language;
	}


	public function get_languages() {
		if( $this->languages === null ) {
			$languages = array();
			if( $this->get_language() !== null )
				$languages[] = $this->get_language();
			foreach( $this->database->return_int_keys(false)->get( 'SELECT DISTINCT `language` FROM `%TABLE` ORDER BY `language`' ) as $lang )
				$languages[] = $lang['language'];
			$this->languages = array_unique( $languages );
		}
		return $this->languages;
	}


	public function get( $key ) {
		if( strlen($key) == 0 )
			return null;
		if( $this->dynamic_update ) {
			if( !isset( $this->dictionary->{ $key } ) )
				$this->set_global( $key, $key );
		}
		$string = $this->dictionary->{ $key };
		if( !$string )
			$string = $key;
		return strval( $string );
	}


	public function set_global( $key, $value ) {
		foreach( $this->get_languages() as $lang ) {
			$val = null;
			if( $this->language == $lang )
				$val = $value;
			$this->set( $key, $val, $lang );
		}
	}


	public function set( $key, $value, $lang=null ) {
		if( !$lang )
			$lang = $this->language;
		if( is_string($lang) && is_string($key) ) {
			$this->database->update_insert( array('language' => $lang, 'key' => $key, 'value' => $value), '`language`="'. $lang .'" AND `key`="'. $key .'"' );
			$this->dictionary->{ $key } = $value;
		}
	}


	public function new_language( $lang ) {
		if( !is_string( $lang ) )
			return null;
		foreach( $this->dictionary as $key => $value )
			$this->set( $key, null, $lang );
		return $this;
	}


	public function __get( $key ) {
		return $this->get( $key );
	}


	public function __call( $lang, $key ) {
		$main_language = $this->language;
		$this->set_language( $lang );
		$string = $this->get( $key );
		$this->set_language( $main_language );
		return $string;
	}


	public function __set( $key, $value ) {
		$this->dictionary->{ $key } = $value;
		if( $this->dynamic_update ) {
			if( isset( $this->dictionary->{ $key } ) )
				$this->set( $key, $value );
			else
				$this->set_global( $key, $value );
		}
		else
			$this->changes[] = $key;
	}


}