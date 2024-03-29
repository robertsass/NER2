<?php
namespace Formatter;


interface ARFFInterface {
	
	function addAttribute( $attributeName, $dataType, $callable );

	function export( array $input );
	
	function getChunkerOutput();
	function getSentences();
	function getChunkerAnnotations( $sentenceIndex, $tokenIndex );

}


class ARFF extends \rsCore\CoreClass implements ARFFInterface {
	
	
	private $_relation;
	private $_attributes = array();
	private $_data;
	private $_chunkerOutput;
	private $_sentences;
	private $_treeAnnotations;
	private $_posTags;
	private $_currentSentenceIndex = 0;
	private $_annotatedSentences;
	
	
	protected static function callCallable( $callable, array $params=array() ) {
		if( is_callable( $callable ) ) {
			if( is_array( $callable ) ) {
				return \rsCore\Core::callMethod( $callable[0], $callable[1], $params );
			}
			elseif( is_string( $callable ) ) {
				return forward_static_call_array( $callable, $params );
			}
		}
		return null;
	}


	public function __construct( $relationName, $chunkerOutput, $annotatedSentences ) {
		$this->_relation = $relationName;
		$this->_chunkerOutput = $chunkerOutput;
		$this->_annotatedSentences = explode( "\n", $annotatedSentences );
		$this->extractSentences();
		$this->extractTreeAnnotations();
	}


	public function addAttribute( $attributeName, $dataType, $callable ) {
		$this->_attributes[ $attributeName ] = array(
			'datatype' => $dataType,
			'callable' => $callable
		);
	}


	public function extractSentences() {
		$chunkerOutput = $this->_chunkerOutput;
		$sentences = array();
		foreach( explode( '</s>', $chunkerOutput ) as $sentence ) {
			$sentence = trim( preg_replace( "/^<s>/", "", trim( $sentence ) ) );
			if( $sentence )
				$sentences[] = explode( "\n", $sentence );
		}
		$this->_sentences = $sentences;
	}


	public function extractTreeAnnotations() {
		$chunkerOutput = $this->getChunkerOutput();
		$sentences = $this->getSentences();
		
		$cleanedSentences = array();
		$treeAnnotations = array();
		$posTags = array();
		$treeLevels = array();
		foreach( $sentences as $i => $sentence ) {
			$lastAnnotationMark = null;
			foreach( $sentence as $j => $token ) {
				$isTerminal = false;
				$treeAnnotationMark = null;
				if( $token == '<s>' )
					$isTerminal = false;
				elseif( $token == '<NC>' )
					$treeLevels['NC'] += 1;
				elseif( $token == '</NC>' )
					$treeLevels['NC'] -= 1;
				elseif( $token == '<VC>' )
					$treeLevels['VC'] += 1;
				elseif( $token == '</VC>' )
					$treeLevels['VC'] -= 1;
				elseif( $token == '<PC>' )
					$treeLevels['PC'] += 1;
				elseif( $token == '</PC>' )
					$treeLevels['PC'] -= 1;
				else
					$isTerminal = true;
				if( !$isTerminal ) {
					unset( $sentences[ $i ][ $j ] );
				}
				else {
					foreach( $treeLevels as $currentMark => $level ) {
						if( $level > 0 )
							$treeAnnotationMark = $currentMark;
					}
					$mark = ($treeAnnotationMark ? ($lastAnnotationMark == $treeAnnotationMark ? 'I-' : 'B-') : ''). $treeAnnotationMark;
					$tokenSplit = explode( "\t", $token );
					$tokenAnnotations = array(
						'token' => $tokenSplit[0],
						'stem'	=> $tokenSplit[2],
						'pos_tag'	=> $tokenSplit[1],
						'tree_annotation'	=> $mark
					);
					$posTags[ $tokenSplit[1] ] = 1;
					$treeAnnotations[ $i ][] = $tokenAnnotations;
					$cleanedSentences[ $i ][] = $tokenSplit[0];
				}
				$lastAnnotationMark = $treeAnnotationMark;
			}
		}
		
		$this->_posTags = array_keys( $posTags );
		$this->_sentences = $cleanedSentences;
		$this->_treeAnnotations = $treeAnnotations;
	}
	
	
	public function getChunkerOutput() {
		return $this->_chunkerOutput;
	}
	
	
	public function getSentences() {
		return $this->_sentences;
	}
	
	
	public function getPOSTags() {
		return $this->_posTags;
	}
	
	
	public function getChunkerAnnotations( $sentenceIndex, $tokenIndex ) {
		return $this->_treeAnnotations[ $sentenceIndex ][ $tokenIndex ];
	}
	
	
	public function getCurrentAnnotations( $tokenIndex ) {
		if( isset( $this->_treeAnnotations[ $this->_currentSentenceIndex ][ $tokenIndex ] ) )
			return $this->_treeAnnotations[ $this->_currentSentenceIndex ][ $tokenIndex ];
		return null;
	}
	
	
	public function getAnnotatedSentence() {
		if( isset( $this->_annotatedSentences[ $this->_currentSentenceIndex ] ) )
			return $this->_annotatedSentences[ $this->_currentSentenceIndex ];
		return '';
	}


	public function convert() {
		return $this->export( $this->getSentences() );
	}


	public function export( array $input ) {
		if( $this->_data === null ) {
			$data = array();
			if( is_array( current( $input ) ) ) {
				foreach( $input as $tokens )
					$data = array_merge( $data, $this->buildData( $tokens ) );
			} else {
				$data = $this->buildData( $input );
			}
			$this->_data = implode( "\n", $data );
		}
		$this->_currentSentenceIndex = 0;

		$output[] = '@RELATION '. $this->_relation;
		$output[] = '';
		
		foreach( $this->_attributes as $attributeName => $attribute ) {
			$output[] = '@ATTRIBUTE "'. $attributeName .'" '. $attribute['datatype'];
		}
		$output[] = '';

		$output[] = '@DATA';
		$output[] = $this->_data;
		
		return implode( "\n", $output );
	}
	
	
	private function buildData( array $input ) {
		$data = array();
		foreach( $input as $i => $part ) {
			$row = array();
			foreach( $this->_attributes as $attributeName => $attribute ) {
				
				$value = self::callCallable( $attribute['callable'], array( $part, $input, $i, $this ) );
				
				if( $attribute['datatype'] == \Features\Plugin::DATATYPE_NUMERIC )
					if( $value == 0 )
						$value = '0';

				if( $attribute['datatype'] == \Features\Plugin::DATATYPE_BOOL )
					if( $value == 0 )
						$value = 'YES';
					else
						$value = 'NO';
						
				// escapen von Sonderzeichen
				if( \rsCore\StringUtils::containsOne( $value, array(',', "'") ) )
					$value = '"'. $value .'"';
				elseif( \rsCore\StringUtils::containsOne( $value, array(',', '"') ) )
					$value = "'". $value ."'";
				
				$row[] = $value;
			}
			$data[] = implode( ',', $row );
		}
		$this->_currentSentenceIndex++;
		return $data;
	}


}