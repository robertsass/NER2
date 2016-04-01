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
class CoreFiles {


	protected static function getFile( $fileid, $dir=null ) {
		if( IS_BRAINSTAGE_DIR )
			$dir = '../';
		$thumb = '';
		if( isset($_GET['thumb']) ) {
			$thumbsize = intval($_GET['thumb']);
			$thumb = 'thumbs/'. $thumbsize .'/';
		}
		$filesdb = rsMysql::instance( 'files' );
		$filedata = $filesdb->getRow( '`id` = ' . intval($fileid) );
		if( is_array($filedata) && $filedata['id'] == intval($fileid) && self::checkFileRights( $filedata ) ) {
			$filename = explode( '.', $filedata['filename'] );
			$filepath = ($dir ? $dir : '') . 'media/'. $thumb . $filedata['filename'];
			if( $thumb != '' && !file_exists($filepath) && file_exists(($dir ? $dir : '') . 'media/' . $filedata['filename']) )
				self::generateThumb( $filedata['filename'], $thumbsize );
			$content_type = (function_exists('mime_content_type') ? mime_content_type($filepath) : (function_exists('finfo_file') ? finfo_file( finfo_open(FILEINFO_MIME_TYPE), $filepath ) : ''));
			if( $content_type == '' )
				$content_type = self::guessMimeType( strtolower( $filename[count($filename)-1] ) );
			foreach( array(
					'mp4' => 'mpeg',
					'm4a' => 'mpeg'
				) as $mistake => $type )
					$content_type = str_replace( '/'.$mistake, '/'.$type, $content_type );
		}
		elseif( is_array($filedata) && $filedata['id'] = intval($fileid) ) {
			$filepath = ($dir ? $dir : '') . 'static/images/notallowed.png';
			$filename = array(1=>'notallowed.png');
			$content_type = 'image/png';
		}
		if( !isset($filepath) || !file_exists($filepath)) {
			$filepath = ($dir ? $dir : '') . 'static/images/notfound.png';
			$filename = array(1=>'notfound.png');
			$content_type = 'image/png';
		}
		header( 'Content-Type: ' . $content_type );
		header( 'Expires: '. gmdate( 'D, d M Y H:i:s', time()+self::BROWSER_CACHE_FILE_MAX_AGE ) .' GMT' );
		header( 'Cache-Control: max-age='. self::BROWSER_CACHE_FILE_MAX_AGE .', must-revalidate' );
		header( 'Last-Modifed: '. gmdate( 'D, d M Y H:i:s', filemtime($filepath) ) );
		header( 'Content-Length: ' . filesize( $filepath ) );
		header( 'Content-Disposition: '. (isset($_GET['download']) ? 'attachment;' : '') .'filename=' . $filedata['title'] .'.'. strtolower($filename[1]) );
		readfile( $filepath );
		return true;
	}


	public static function guessMimeType( $suffix ) {
		$suffix = strtolower( $suffix );
		$types = array(
			'audio/x-aiff' => array('aiff','aif','aifc'),
			'audio/mpeg' => array('mp2','mp3','m4a'),
			'audio/x-wav' => array('wav'),
			'video/mpeg' => array('mp4','mpeg','mpg','m4v'),
			'video/quicktime' => array('mov','qt'),
			'image/jpeg' => array('jpg','jpeg','jpe'),
			'image/gif' => array('gif'),
			'image/png' => array('png'),
			'image/tiff' => array('tif','tiff'),
			'application/pdf' => array('pdf'),
			'application/zip' => array('zip'),
			'application/rtf' => array('rtf','rtfd','rtfx'),
			'text/html' => array('html','htm','shtml','shtm','xhtml','xhtm'),
			'text/xml' => array('xml'),
			'text/plain' => array('txt','c','h','cpp','js','css'),
			'text/x-vcard' => array('vcf','vcard'),
		);
		foreach( $types as $type => $suffixes )
			if( in_array( $suffix, $suffixes ) )
				return $type;
		return 'application/octet-stream';
	}


	protected static function checkFileRights( $filedata ) {
		if( $filedata['rights'] == 'w' || $filedata['rights'] == '' )
			return true;
		if( $filedata['rights'] == 'p' ) {
			$User = new rsUser();
			if( $filedata['owner'] == $User->get('id') )
				return true;
		}
		return false;
	}


}