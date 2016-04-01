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
interface RequestHandlerInterface {

	static function addRule( $targetIdOrInstance, $targetType );

	static function getRuleById( $ruleId );
	static function getHandlerByRequest( RequestPath $Request );
	static function getRuleByRequest( RequestPath $Request );

	static function detectTypeByTarget( $Target );

	function getDatabaseConnector();
	function getRule();
	function getTargetId();
	function getTargetType();
	function getTarget();

}


/**
 * @author Robert Sass <rs@brainedia.de>
 * @copyright 2014-2015 Robert Sass
 */
class RequestHandler extends CoreClass implements RequestHandlerInterface, CoreFrameworkInitializable {


	const RULES_TABLE	= 'brainstage-url-rules';
	const TREE_TABLE	= 'brainstage-url-tree';

	const TARGETTYPE_DOCUMENT = 'document';
	const TARGETTYPE_FILE = 'file';
	const TARGETTYPE_REDIRECT = 'redirect';
	const TARGETTYPE_URL_RESOLVE = 'url-resolve';

	const ERROR_NO_RULE_FOUND = "No matching rules found to this URL request.";


	private static $_instances;

	private $_Request;
	private $_Rule;


	/* CoreFrameworkInitializable methods */

	public static function frameworkRegistration() {
		$rulesTable = Database::table( self::RULES_TABLE );
		$treeTable = Database::table( self::TREE_TABLE );
		Core::core()->registerDatabaseDatasetHandler( $rulesTable, '\rsCore\RequestHandlerRule' );
		Core::core()->registerDatabaseDatasetHandler( $treeTable, '\rsCore\RequestHandlerNode' );
	}


	/* RequestHandlerInterface methods */

	public static function addRule( $targetIdOrInstance, $targetType ) {
		return RequestHandlerRule::addRule( $targetIdOrInstance, $targetType );
	}


	public static function getRuleById( $ruleId ) {
		$ruleId = intval( $ruleId );
		return Database::table( self::RULES_TABLE )->getById( $ruleId );
	}


	public static function getHandlerByRequest( RequestPath $Request ) {
		if( static::$_instances === null ) {
			static::$_instances = array();
		}
		if( !array_key_exists( $Request->orig, static::$_instances ) )
			static::$_instances[ $Request->orig ] = new static( $Request );
		return static::$_instances[ $Request->orig ];
	}


	public static function getRuleByRequest( RequestPath $Request, $exceptions=true ) {
		$domainComponents = array_reverse( explode( '.', $Request->domain->orig ) );
		$pathComponents = $Request->path;
		$parameters = $Request->parameters;
		$rules = array();


		foreach( array($Request->domain->orig, '*') as $pattern ) {
			$Node = RequestHandlerNode::getByColumns( array(
				'pattern' => $pattern,
				'type' => 'fulldomain'
			) );
			if( $Node ) {
 				$Rule = RequestHandlerRule::getByPrimaryKey( $Node->ruleId );
				$rules[] = $Rule;
			}
		}
		
		if( empty( $rules ) )
			$rules = self::traverseTree( $domainComponents, 'domain' );
		
		$Rule = array_pop( $rules );
		if( $Rule->targetType == self::TARGETTYPE_URL_RESOLVE ) {
			$Node = $Rule->getTarget();
			$rules = self::traverseTree( $pathComponents, 'path', $Node );

			if( empty( $rules ) && $Node ) {
				$component = array_shift( $pathComponents );
				if( $Node->pattern == $component || $Node->pattern == '*' ) {
					$Rule = RequestHandlerRule::getByPrimaryKey( $Node->ruleId );
					if( $Rule )
						$rules[] = $Rule;
					$rules = array_merge( $rules, self::traverseTree( $pathComponents, 'path', $Node ) );
				}
			}
		} else {
			$rules[] = $Rule;
		}
		
		do {
			$Rule = array_pop( $rules );
			$Target = $Rule->getTarget();
		} while( !empty( $rules ) && !$Target );
		
		if( $Rule )
			return $Rule;

		if( $exceptions )
			throw new Exception( self::ERROR_NO_RULE_FOUND );
		return null;
	}


	public static function detectTypeByTarget( $Target ) {
		if( $Target instanceof RequestHandlerNode )
			return self::TARGETTYPE_URL_RESOLVE;
		elseif( $Target instanceof \Brainstage\Document )
			return self::TARGETTYPE_DOCUMENT;
		elseif( $Target instanceof RequestHandlerRedirect )
			return self::TARGETTYPE_REDIRECT;
		elseif( $Target instanceof \Brainstage\File )
			return self::TARGETTYPE_FILE;
	}


	public function __construct( RequestPath $Request ) {
		$this->_Request = $Request;
		$this->_Rule = static::getRuleByRequest( $Request );
	}


	public function getDatabaseConnector() {
		return $this->_Request->getDatabaseConnector();
	}


	public function getRule() {
		return $this->_Rule;
	}


	public function getTargetId() {
		return $this->getRule()->targetId;
	}


	public function getTargetType() {
		return $this->getRule()->targetType;
	}


	public function getTarget() {
		if( $this->getTargetType() == self::TARGETTYPE_URL_RESOLVE ) {
			return RequestHandlerNode::getById( $this->getTargetId() );
		}
		elseif( $this->getTargetType() == self::TARGETTYPE_DOCUMENT ) {
			return \Brainstage\Document::getDocumentById( $this->getTargetId(), null );
		}
		elseif( $this->getTargetType() == self::TARGETTYPE_REDIRECT ) {
			return RequestHandlerRedirect::getById( $this->getTargetId() );
		}
		elseif( $this->getTargetType() == self::TARGETTYPE_FILE ) {
			return \Brainstage\File::getById( $this->getTargetId() );
		}
		return null;
	}


	private static function traverseTree( $components, $type, RequestHandlerNode $ParentNode=null ) {
		if( !is_array( $components ) )
			$components = array( $components );
		$rules = array();
		$nodes = array();
		$component = array_shift( $components );
		foreach( array($component, '*') as $component ) {
			$Node = self::getNode( $component, $type, $ParentNode );
			if( $Node ) {
				$nodes[] = $Node;
				$Rule = RequestHandlerRule::getByPrimaryKey( $Node->ruleId );
				if( $Rule ) {
					$rules[] = $Rule;
				}
				$rules = array_merge( $rules, self::traverseTree( $components, $type, $Node ) );
				if( $Rule ) {
					break;
				}
			}
		}
		return $rules;
	}


	private static function getNode( $pattern, $type=null, $ParentNode=null ) {
		$parameters = array( 'pattern' => $pattern );
		if( $type !== null )
			$parameters['type'] = $type;
		if( $ParentNode === null ) {
			$ParentNode = RequestHandlerNode::getDatabaseConnection()->getRoot();
		}
		$children = RequestHandlerNode::getChildrenByColumns( $ParentNode, $parameters );
		return is_array( $children ) ? current( $children ) : $children;
	}


}