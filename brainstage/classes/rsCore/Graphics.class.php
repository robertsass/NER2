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
class Graphics {


	public static function generateThumb( $file, $width, $height=null ) {
		if( !$height )
			$height = $width;
		$src = $file;
		$destdir = dirname( $file ) .'/thumbs/'. $width .'/';
		$dest = $destdir. $file;
		if( !file_exists($destdir) )
			mkdir($destdir);
		copy( $src, $dest );
		$suffix = array_pop( explode( '.', strtolower($file) ) );
		if( $suffix == 'png' )
			self::resize_png( $dest, $width, $height );
		else
			self::resize_jpeg( $dest, $width, $height );
	}


	public static function resizePNG( $file, $width, $height ) {
		$img = imagecreatefrompng( $file );
		if( imagesx($img) > $width || imagesy($img) > $height ) {
			$new_width = $width;
			$new_height = $height;
			if( imagesx($img) > imagesy($img) )
				$new_height = imagesy($img) * ( $width/imagesx($img) );
			else
				$new_width = imagesx($img) * ( $height/imagesy($img) );
			$img2 = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled( $img2, $img, 0, 0, 0, 0, $new_width, $new_height, imagesx($img), imagesy($img) );
			imagedestroy($img);
			$img = $img2;
		}
		imagepng( $img, $file );
		imagedestroy( $img );
	}


	public static function resizeJPEG( $file, $width, $height ) {
		$img = imagecreatefromjpeg( $file );
		if( imagesx($img) > $width || imagesy($img) > $height ) {
			$new_width = $width;
			$new_height = $height;
			if( imagesx($img) > imagesy($img) )
				$new_height = imagesy($img) * ( $width/imagesx($img) );
			else
				$new_width = imagesx($img) * ( $height/imagesy($img) );
			$img2 = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled( $img2, $img, 0, 0, 0, 0, $new_width, $new_height, imagesx($img), imagesy($img) );
			imagedestroy($img);
			$img = $img2;
		}
		imagejpeg( $img, $file );
		imagedestroy( $img );
	}


}