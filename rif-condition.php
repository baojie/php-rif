<?php
// RIF Core condition language
// This file contains classes for handling annotations 
// It imports two other files for Term and Formula classes
// Jie Bao 2011-01-31

require_once("../rif-condition-term.php"); // all kinds of terms
require_once("../rif-condition-formula.php"); // all kinds of formulas

// IRIMETA        ::= '(*' IRICONST? (Frame | 'And' '(' Frame* ')')? '*)'
class Annotation{
	public $frame = null; // array of Frame
	public $iri = null; // ConstType

	function parse($xml_content){		
		$this->frame = array();		
		// single Frame
		if (isset($xml_content->meta->Frame)){
			$f = new Frame; $f->parse($xml_content->meta->Frame);
			$this->frame[] = $f;
		}
		// conjunction of Frames
		else if (isset($xml_content->meta->And->formula)){			
			foreach ($xml_content->meta->And->formula as $frame_item){
				$f = new Frame; $f->parse($frame_item->Frame);
				$this->frame[] = $f;		
			}
		}
		
		if (isset($xml_content->id))
		{
			$this->iri = new ConstType();
			$this->iri->parse($xml_content->id);
		}
		//print_r($this);
	}
}

// the base class of all classes that have annotations
class RifElement{
	public $annotation; // instance of Annotation
	
	function parseAnnotation($xml_content){
		if (isset($xml_content->meta) || isset($xml_content->id)){
			$this->annotation = new Annotation;
			$this->annotation->parse($xml_content);				
		}	
	}
}
?>