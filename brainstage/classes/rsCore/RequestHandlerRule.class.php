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
interface RequestHandlerRuleInterface {

	static function addRule( $targetIdOrInstance, $targetType );
	static function getRule( $targetIdOrInstance, $targetType );

	function getTarget();

	function remove();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class RequestHandlerRule extends DatabaseDatasetAbstract implements RequestHandlerRuleInterface {


	const ERROR_CANT_DUPLICATE = "Can't duplicate rule.";
	const ERROR_TARGET_NOT_FOUND = "Very strange... Target could not be determined.";
	const ERROR_INVALID_TARGETID = "Invalid target id.";
	const ERROR_INVALID_TARGETTYPE = "Invalid target type.";


	protected static $_databaseTable = RequestHandler::RULES_TABLE;


	/* Static methods */

	public static function addRule( $targetIdOrInstance, $targetType=null ) {
		if( $targetType === null && is_object( $targetIdOrInstance ) )
			$targetType = RequestHandler::detectTypeByTarget( $targetIdOrInstance );
		if( !$targetType )
			throw new Exception( self::ERROR_INVALID_TARGETTYPE );

		if( is_object( $targetIdOrInstance ) )
			$targetId = $targetIdOrInstance->getPrimaryKeyValue();
		elseif( is_int( $targetIdOrInstance ) || intval( $targetIdOrInstance ) )
			$targetId = intval( $targetIdOrInstance );
		else
			throw new Exception( self::ERROR_INVALID_TARGETID );

		$Rule = self::getRule( $targetIdOrInstance, $targetType );
		if( !$Rule ) {
			$Rule = self::create();
			if( $Rule ) {
				$Rule->targetId = $targetId;
				$Rule->targetType = $targetType;
				$Rule->adopt();
			}
		}
		return $Rule;
	}


	public static function getRule( $targetIdOrInstance, $targetType=null ) {
		if( $targetType === null && is_object( $targetIdOrInstance ) )
			$targetType = RequestHandler::detectTypeByTarget( $targetIdOrInstance );
		if( !$targetType )
			throw new Exception( self::ERROR_INVALID_TARGETTYPE );
		if( is_object( $targetIdOrInstance ) )
			$targetId = $targetIdOrInstance->getPrimaryKeyValue();
		elseif( is_int( $targetIdOrInstance ) || intval( $targetIdOrInstance ) )
			$targetId = intval( $targetIdOrInstance );
		else
			throw new Exception( self::ERROR_INVALID_TARGETID );

		return self::getByColumns( array(
			'targetId' => $targetId,
			'targetType' => $targetType
		) );
	}


	/* Public methods */

	public function duplicate() {
		throw new Exception( self::ERROR_CANT_DUPLICATE );
	}


	public function getTarget() {
		if( $this->targetType == RequestHandler::TARGETTYPE_REDIRECT ) {
			return RequestHandlerRedirect::getById( $this->targetId );
		}
		if( $this->targetType == RequestHandler::TARGETTYPE_DOCUMENT ) {
			return \Brainstage\Document::getDocumentById( $this->targetId, null );
		}
		if( $this->targetType == RequestHandler::TARGETTYPE_FILE ) {
			return \Brainstage\File::getById( $this->targetId );
		}
		if( $this->targetType == RequestHandler::TARGETTYPE_URL_RESOLVE ) {
			return RequestHandlerNode::getById( $this->targetId );
		}
		throw new Exception( self::ERROR_TARGET_NOT_FOUND );
	}


	/** Löscht die Rule
	 * @return boolean
	 * @api
	 */
	public function remove() {
		return parent::remove();
	}


}