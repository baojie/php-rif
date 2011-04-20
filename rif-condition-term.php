<?php
// Jie Bao 2011-01-31
// Classes for Term

/*
TERM           ::= IRIMETA? (Const | Var | List | 'External' '(' Expr ')')
Const          ::= '"' UNICODESTRING '"^^' SYMSPACE | CONSTSHORT
Var            ::= '?' Name
List           ::= 'List' '(' GROUNDTERM* ')'
GROUNDUNITERM  ::= Const '(' GROUNDTERM* ')'
GROUNDTERM     ::= IRIMETA? (Const | List | 'External' '(' GROUNDUNITERM ')')
Expr           ::= UNITERM
UNITERM        ::= Const '(' (TERM* ')'
Name           ::= NCName | '"' UNICODESTRING '"'
SYMSPACE       ::= ANGLEBRACKIRI | CURIE
ANGLEBRACKIRI ::= IRI_REF
SYMSPACE      ::= ANGLEBRACKIRI | CURIE
CURIE         ::= PNAME_LN | PNAME_NS
Const         ::= '"' UNICODESTRING '"^^' SYMSPACE | CONSTSHORT
CONSTSHORT    ::= ANGLEBRACKIRI              // shortcut for "..."^^rif:iri
                 | CURIE                      // shortcut for "..."^^rif:iri
                 | '"' UNICODESTRING '"'      // shortcut for "..."^^xs:string
                 | NumericLiteral             // shortcut for "..."^^xs:integer,xs:decimal,xs:double
                 | '_' NCName                   // shortcut for "..."^^rif:local
                 | '"' UNICODESTRING '"' '@' langtag             // shortcut for "...@..."^^rdf:PlainLiteral
*/

//TERM           ::= IRIMETA? (Const | Var | List | 'External' '(' Expr ')')
class Term 
{
	public $type = "";
	public $content = null;
	
	function parse($xml_content){	
		$name = $xml_content->getName();
		if (!isset($name)) $name = '';		
		
		if (isset($xml_content->Const) || $name == 'Const')	{
			//print_r($xml_content);
			$this->type = 'Const' ;
			$this->content = new ConstType();
			$this->content->parse(isset($xml_content->Const)?$xml_content->Const:$xml_content);
			//print_r($this);
		}
		else if (isset($xml_content->Var) || $name == 'Var') {
			$this->type = 'Var' ;
			$this->content = new VarType();
			$this->content->parse(isset($xml_content->Var)?$xml_content->Var:$xml_content);
		}
		else if (isset($xml_content->List) || $name == 'List') {
		    //print_r($xml_content);
			$this->type = 'List' ;
			$this->content = new ListType();
			$this->content->parse(isset($xml_content->List)?$xml_content->List:$xml_content);
		}
		else if (isset($xml_content->External) || $name == 'External') {	
			$this->type = 'External' ;
			$this->content = new External();
			$this->content->parse(isset($xml_content->External)?$xml_content->External:$xml_content);			
		}
		else {
			$this->type = 'Unknown';
		}
	}
}

// List           ::= 'List' '(' GROUNDTERM* ')'
// GROUNDTERM     ::= IRIMETA? (Const | List | 'External' '(' GROUNDUNITERM ')')
class ListType  extends RifElement{
	public $items; // array of Term
	function parse($xml_content){
		//print_r($xml_content);
		$this->parseAnnotation($xml_content);
		$this->items = array();
		foreach($xml_content->items->children() as $item){
		    //print_r($item->getName()."\n");
			$t = new Term(); $t->parse($item);
			$this->items[] = $t;
		}
		//print_r($this);
	}
}

// Const          ::= '"' UNICODESTRING '"^^' SYMSPACE | CONSTSHORT
class ConstType extends RifElement
{
	public $type = '';  // string
	public $content = ''; // string
		function parse($xml_content)	{		
		if (isset($xml_content->Const)) 
			$xml_content = $xml_content->Const;
		$this->parseAnnotation($xml_content);
		
		if (isset($xml_content->attributes()->type))
		{
			$this->type = (string)$xml_content->attributes()->type;			
		}
		
		$arr = array();
		$x = XMLToArrayFlat($xml_content, $arr);
				
		$this->content = (string)$arr[''];
	}
}

// Var            ::= '?' Name
class VarType extends RifElement{
	public $name ="";	
	function parse($xml_content){
		//print_r($xml_content);
		$this->parseAnnotation($xml_content);		
		$this->name = (string)$xml_content;
	}
}

// Expr           ::= UNITERMclass External extends UNITERM{
	function parse($xml_content){
	    //print_r($xml_content);
		$this->parseAnnotation($xml_content);
		// a Term external
		if (isset($xml_content->content->Expr))
			parent::parse($xml_content->content->Expr);
		// a Formula external
		else if (isset($xml_content->content->Atom))
			parent::parse($xml_content->content->Atom);
	}
}

// UNITERM        ::= Const '(' (TERM* ')'
class UNITERM extends RifElement{	
	public $op = null;  // ConstType
	public $arg = null; // array of Term
	
	function parse($xml_content)
	{			
		// the operator part
		$op_xml = $xml_content->op;			
		$this->op = new ConstType();
		$this->op->parse($op_xml);
		
		// the argument part
		$this->arg = array();
		$args_xml = $xml_content->args;	
		
		foreach ($args_xml->children() as $term){
			$parsed_term = new Term(); 
			$parsed_term->parse($term);
			$this->arg[]= $parsed_term;				
		}		
	}	
}
?>