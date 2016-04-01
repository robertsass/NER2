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
 * @internal
 */
interface CurlResponseInterface {

	function getStatus();
	function getResponse();
	function getError();

	function getJson();
	function decodeJson();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class CurlResponse extends CoreClass implements CurlResponseInterface {


	public $response;
	public $status;
	public $url;
	public $error;


	/** Gibt den HTTP Status-Code zurück
	 * @return integer
	 * @api
	 */
	public function getStatus() {
		return $this->status;
	}


	/** Gibt den HTTP Response Body zurück
	 * @return string
	 * @api
	 */
	public function getResponse() {
		return $this->response;
	}


	/** Gibt eventuelle Fehler zurück
	 * @return mixed
	 * @api
	 */
	public function getError() {
		return $this->error;
	}


	/** Versucht die Antwort als JSON zu dekodieren; Alias zu decodeJson()
	 * @return string
	 * @api
	 */
	public function getJson() {
		return $this->decodeJson();
	}


	/** Versucht die Antwort als JSON zu dekodieren
	 * @return string
	 * @api
	 */
	public function decodeJson() {
		return json_decode( $this->response );
	}


}