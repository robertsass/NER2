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
class Useragent {

	/* Browser */
	const IE = 'MSIE';
	const FIREFOX = 'Firefox';
	const WEBKIT = 'WebKit';
	const SAFARI = 'Safari';
	const CHROMIUM = 'Chomium';
	const CHROME = 'Chrome';
	const OPERA = 'Opera';
	const OTHER = 'other';

	/* Mobile Browser */
	const MOBILESAFARI = 'Mobile Safari';

	/* Engines */
	const E_WEBKIT = 'WebKit';
	const E_GECKO = 'Gecko';
	const E_TRIDENT = 'Trident';

	/* Plattformen */
	const WIN = 'Windows';
	const MAC = 'Mac';
	const LINUX = 'Linux';

	/* Mobile Systeme */
	const IOS = 'iOS';
	const ANDROID = 'Android';

	/* Geräte */
	const PC = 'PC';
	const IPHONE = 'iPhone';
	const IPAD = 'iPad';
	const IPOD = 'iPod';



	protected static $obj = null;
	private static $_instance = null;

    final public static function getInstance() {
		if(!self::$_instance) {
			self::$_instance = new self();
            @self::detectUseragent();
		}
		return self::$_instance;
	}

    final public static function myself() {
        return self::getInstance();
    }


	final private function detectUseragent() {
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$platform = self::OTHER;
		$version = null;
		$device = self::PC;

		if( preg_match( '/Android/i', $u_agent ) )
			$platform = self::ANDROID;
		elseif( preg_match( '/linux/i', $u_agent ) )
			$platform = self::LINUX;
		elseif( preg_match( '/iphone|iphone os/i', $u_agent ) ) {
			$platform = self::IOS;
			$device = self::IPHONE;
		}
		elseif( preg_match( '/ipad/i', $u_agent ) ) {
			$platform = self::IOS;
			$device = self::IPAD;
		}
		elseif( preg_match( '/ipod/i', $u_agent ) ) {
			$platform = self::IOS;
			$device = self::IPOD;
		}
		elseif( preg_match( '/macintosh|mac os x/i', $u_agent ) )
			$platform = self::MAC;
		elseif( preg_match( '/windows|win32/i', $u_agent ) )
		    $platform = self::WIN;

		if( preg_match( '/webkit/i', $u_agent ) )
			$engine = self::E_WEBKIT;
		elseif( preg_match( '/gecko/i', $u_agent ) )
			$engine = self::E_GECKO;
		elseif( preg_match( '/trident/i', $u_agent ) )
			$engine = self::E_TRIDENT;

		if( preg_match( '/MSIE/i', $u_agent ) && !preg_match( '/Opera/i', $u_agent ) )
			$ub = self::IE;
		elseif( preg_match( '/Firefox/i', $u_agent ) )
			$ub = self::FIREFOX;
		elseif( preg_match( '/Chrome/i', $u_agent ) )
			$ub = self::CHROME;
		elseif( preg_match( '/Mobile Safari/i', $u_agent ) )
			$ub = self::MOBILESAFARI;
		elseif( preg_match( '/Safari/i', $u_agent ) )
			$ub = self::SAFARI;
		elseif( preg_match( '/Opera/i', $u_agent ) )
		    $ub = self::OPERA;
		elseif( preg_match( '/Netscape/i', $u_agent ) )
			$ub = self::OTHER;

		$known = array( 'Version', $ub, 'other' );
		$pattern = '#(?<browser>' . join( '|', $known ) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		preg_match_all( $pattern, $u_agent, $matches );

		$i = count( $matches['browser'] );
		if( $i != 1 ) {
		    if( strripos( $u_agent, 'Version' ) < strripos( $u_agent, $ub ) )
		        $version = $matches['version'][0];
		    else
		        $version = $matches['version'][1];
		}
		else
		    $version = $matches['version'][0];

		if( $version == null || $version == "" )
			$version=null;

		$os_info = explode( ';', array_pop( explode( '(', array_shift( explode( ')', $u_agent ) ) ) ) );
		foreach( $os_info as $part ) {
			$part = trim($part);
			$s = $platform;
			if( $platform == self::IOS )
				$s = 'os';
			if( substr_count( strtolower($part), strtolower($s) ) > 0 ) {
				foreach( explode( ' ', $part ) as $subpart ) {
					$subpart = str_replace( '_', '.', $subpart );
					if( is_numeric(str_replace( '.', '', $subpart )) ) {
						$os_version = $subpart;
						$os_title = $part;
						break;
					}
				}
			}
			if( isset($os_version) )
				break;
		}
		if( $platform == self::ANDROID && count($os_info) > 3 ) {
			$device_string = trim($os_info[count($os_info)-1]);
			$device_string_parts = explode('Build/', $device_string);
			$device_build = trim( $device_string_parts[1] );
			$device = trim( $device_string_parts[0] );
		}

		$obj = new DataClass();
		$obj->string = $_SERVER['HTTP_USER_AGENT'];
		$obj->browser = new DataClass();
		$obj->browser->engine = $engine;
		$obj->browser->name = $ub;
		$obj->browser->version = $version;
		$obj->browser->languages = self::detectLanguages();
		$obj->os = new DataClass();
		$obj->os->platform = $platform;
		$obj->os->name = $os_title;
		$obj->os->version = $os_version;
		$obj->device = new DataClass();
		$obj->device->name = $device;
		if( isset($device_build,$device_string) ) {
			$obj->device->build = $device_build;
			$obj->device->string = $device_string;
		}

		self::$obj = $obj;
	}


	final public static function get( $name ) {
		$_self = self::getInstance();

		if( isset(self::$obj->$name) ) {
			return self::$obj->$name;
        }
		return null;
	}


    final public static function check( $condition ) {
        return self::is($condition);
    }

    final public static function is( $condition ) {
        $_self = self::getInstance();

        $orEqual = false;
        $conditions = preg_split( '[ |<|>|=]', $condition, 3 );
        if( count($conditions) > 2 ) {
            $orEqual = true;
            $version_condition = trim( $conditions[2] );
        } elseif( count($conditions) > 1 ) {
            $version_condition = trim( $conditions[1] );
        }
        $agent_condition = strtoupper( trim( $conditions[0] ) );
        $conditions_operator = strpos($condition, '<') === false ? (
                strpos($condition, '>') === false ? '=' : '>'
            ) : '<';

        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        if( isset($constants[$agent_condition]) ) {
            if( $constants[$agent_condition] == self::$obj->browser->name ) {
                if( isset($version_condition) ) {
                    $users_version = explode( '.', self::$obj->browser->version );
                    $conditions_version = explode( '.', $version_condition );
                    for( $i=0; $i<count($conditions_version); $i++ ) {
                        $uv = isset($users_version[$i]) ? intval($users_version[$i]) : 0;
                        $cv = intval($conditions_version[$i]);
                        if( $conditions_operator === '<' ) {
                            if( $orEqual ? $uv > $cv : $uv >= $cv )
                               return false;
                        } elseif( $conditions_operator === '>' ) {
                            if( $orEqual ? $uv < $cv : $uv <= $cv )
                                return false;
                        } else {    // $conditions_operator === '=' || $conditions_operator === '=='
                            if( $uv !== $cv )
                                return false;
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }


	final public static function getObject() {
		$_self = self::getInstance();
		return self::$obj;
	}


	final public static function parseHttpAcceptLanguage( $string ) {
		if( $string == '' )
			return array();

		$string = strtolower( $string );
		preg_match_all( '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $string, $lang_parse );
		if( count( $lang_parse[1] ) )
			$languages = array_combine( $lang_parse[1], $lang_parse[4] );
		foreach( $languages as $lang => $val )
			if( $val == '' )
				$languages[ $lang ] = 1;
		arsort( $languages, SORT_NUMERIC );

		return $languages;
	}


	final public static function detectLanguages() {
		if( array_key_exists( 'HTTP_ACCEPT_LANGUAGE', $_SERVER ) )
			return self::parseHttpAcceptLanguage( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
		return array();
	}


}