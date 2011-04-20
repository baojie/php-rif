<?php

# proof of concept implementation of RIF-BLD and stratified RIF-PRD
# Jie Bao <baojie@gmail.com> Jan 21, 2011

# this file is a parser for RIF

require_once("../rif-condition.php");

//   Implies        ::= IRIMETA? (ATOMIC | 'And' '(' ATOMIC* ')') ':-' FORMULA
class Implies extends RifElement
{
	public $if; // Formula
	public $then; // Formula
	
	function parse($xml_content)
	{	
		$this->parseAnnotation($xml_content);
		
		$this->if = new Formula();
		$this->if->parse($xml_content->if);

		// then part, 'Atom' or 'And' of 'Atom's		
		$this->then = new Formula();
		$this->then->parse($xml_content->then);				
	}
}

//CLAUSE         ::= Implies | ATOMIC
//ATOMIC         ::= IRIMETA? (Atom | Frame)
class Clause{
	public $type; // Implies, Atom, or Frame
	public $content;
	function parse($xml_content){	
		if ( isset ($xml_content->Implies)){	
			$this->type = 'Implies';
			$this->content = new Implies();
			$this->content->parse($xml_content->Implies);
		}	
		else if ( isset ($xml_content->Atom)){	
			$this->type = 'Atom';
			$this->content = new Atom();
			$this->content->parse($xml_content->Atom);				
		}	
		else if ( isset ($xml_content->Frame)){	
			$this->type = 'Frame';
			$this->content = new Frame();
			$this->content->parse($xml_content->Frame);				
		}	
	}
}

//RULE           ::= (IRIMETA? 'Forall' Var+ '(' CLAUSE ')') | CLAUSE
//CLAUSE         ::= Implies | ATOMIC
class Forall extends RifElement{
	public $var; // array
	public $clause;

	function parse($xml_content){
		$this->parseAnnotation($xml_content);
		
		// a rule may be a clause with or without "Forall" variable declaration 
		$this->var = array();
		$this->clause = array();
		foreach ($xml_content->declare as $declare){
			$v = new VarType(); $v->parse($declare->Var);
			$this->var[] = $v;								
		}
		
		// a formula may be an Implies or an ATOMIC		
		if ( isset ($xml_content->formula)){			
			$f = new Clause(); $f->parse($xml_content->formula);
			$this->clause = $f;								
		}
	}
}

//Group          ::= IRIMETA? 'Group' '(' (RULE | Group)* ')'
//RULE           ::= (IRIMETA? 'Forall' Var+ '(' CLAUSE ')') | CLAUSE
//CLAUSE         ::= Implies | ATOMIC
class Group extends RifElement
{
	public $sentences;
	
	function parse($xml_content)
	{
		// annotation
		$this->parseAnnotation($xml_content);
		
		$this->sentences = array();
		foreach ($xml_content->sentence as $sentence){
			// a sentence may be a Group or a Clause with/without Forall declarations
			//print_r($sentence->getName()."\n");
			// groups
			if(isset($sentence->Group)){
				$g = new Group(); $g->parse($sentence->Group);
				$this->sentences[] = $g;
			}
			else if (isset($sentence->Forall)){
				$r = new Forall(); $r->parse($sentence->Forall);
				$this->sentences[] = $r;
			}
			else{ // clause with no forall declaration 
				$f = new Clause(); $f->parse($sentence);
			    $this->sentences[] = $f;	
			}
		}
	}
}

//Import         ::= IRIMETA? 'Import' '(' LOCATOR PROFILE? ')'
//LOCATOR        ::= ANGLEBRACKIRI
//PROFILE        ::= ANGLEBRACKIRI
Class Import extends RifElement{
	public $locator = null;
	public $profile = null;
	
	function parse($xml_content){
		$this->parseAnnotation($xml_content);	
		$this->locator = (string) $xml_content->location;
		if (isset($xml_content->profile))
			$this->profile = (string) $xml_content->profile;
	}
}

//Document       ::= IRIMETA? 'Document' '(' Base? Prefix* Import* Group? ')'
class RifDocument extends RifElement
{
	public $base; // string (url)
	public $prefix; // array of string=>string
	public $imports; // array of Import
	public $group;	// array of Group
	
	function parse($xml_content){
		// prefix
		$this->prefix = $xml_content->getDocNamespaces(true);
		//print_r($xml_content->attributes());		
					
		// annotation
		$this->parseAnnotation($xml_content);	
				
		// get import
		$this->import = array();
		foreach ($xml_content->directive as $directive){
			// Import         ::= IRIMETA? 'Import' '(' LOCATOR PROFILE? ')'
			// LOCATOR        ::= ANGLEBRACKIRI
			// PROFILE        ::= ANGLEBRACKIRI
			// ANGLEBRACKIRI ::= IRI_REF
			$imp = new Import();
			$imp->parse($directive->Import);
			$this->imports[] = $imp;
		}
		
		// get group
		$this->group = array();
		if (isset ($xml_content->payload)){
			$payload = $xml_content->payload;
			foreach ($payload->Group as $Group){
				$g = new Group; $g->parse($Group);
				$this->group[] = $g;				
			}				
		}
	}
	
	// regular expression may not be the best way to parse it
	// @todo: find a more efficient wayt using somw XML parser
	function parseBase($file)
	{
		$xmlstr = file_get_contents($file);
		$pattern = "/<Document.*?\sxml:base\s*=\s*\"(.*?)\".*?>/sm";
		preg_match($pattern, $xmlstr, $matches);
		if(isset($matches[1]))
			$this->base = $matches[1];
	}
	
	function load($file)
	{
		if (file_exists($file)) {			
			$xml = simplexml_load_file($file);
			$this->parseBase($file);
			//print_r($xml);
			$this->parse($xml);
		}else {
			exit("Failed to open $file.");
		}
	}
}

?>