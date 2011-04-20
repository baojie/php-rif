<?php
// Jie Bao 2011-01-31
// Classes for Formula

/*
FORMULA        ::= IRIMETA? 'And' '(' FORMULA* ')' |
                     IRIMETA? 'Or' '(' FORMULA* ')' |
                     IRIMETA? 'Exists' Var+ '(' FORMULA ')' |
                     ATOMIC |
                     IRIMETA? Equal |
                     IRIMETA? Member |
                     IRIMETA? 'External' '(' Atom ')'
ATOMIC         ::= IRIMETA? (Atom | Frame)
Atom           ::= UNITERM
Equal          ::= TERM '=' TERMMember         ::= TERM '#' TERMFrame          ::= TERM '[' (TERM '->' TERM)* ']'					 
*/
require_once("../rif-condition.php");

class Formula
{
	public $type = "";
	public $content;
	
	function parse($xml_content)
	{		
		if (isset($xml_content->And)){
			$this->type = 'And';
			$this->content = new AndFormula();
			$this->content->parse($xml_content->And);
		}
		else if (isset($xml_content->Or)){
			$this->type = 'Or';
			$this->content = new OrFormula();
			$this->content->parse($xml_content->Or);
		}
		else if (isset($xml_content->Atom)){
			$this->type = 'Atom';
			$this->content = new Atom();
			$this->content->parse($xml_content->Atom);
		}
		else if (isset($xml_content->Frame)){
			$this->type = 'Frame';
			$this->content = new Frame();
			$this->content->parse($xml_content->Frame);
		}
		else if (isset($xml_content->Exists)){
			$this->type = 'Exists';
			$this->content = new Exists();
			$this->content->parse($xml_content->Exists);
		}
		else if (isset($xml_content->Equal)){
			$this->type = 'Equal';		
			$equ = new Equal(); $equ->parse($xml_content->Equal);
			$this->content = $equ;	
		}
		else if (isset($xml_content->Member)){
			$this->type = 'Member';	
			$this->content = new Member(); 
			$this->content->parse($xml_content->Member);				
		}		
		else if (isset($xml_content->External)){
			$this->type = 'External';	
			$this->content = new External(); 
			$this->content->parse($xml_content->External);				
		}		
	}
}

// <And><formula>...</formula><formula>...</formula></And>
class AndFormula extends RifElement{
	public $formula; // array of Formula
	
	function parse($xml_content){
		$this->parseAnnotation($xml_content);
		foreach ($xml_content->formula as $f_xml){
			$f = new Formula(); $f->parse($f_xml);
			$this->formula[] = $f;
		}
	}
}

// <Or><formula>...</formula><formula>...</formula></Or>
class OrFormula extends RifElement{
	public $formula; // array of Formula
	
	function parse($xml_content){
		$this->parseAnnotation($xml_content);
		foreach ($xml_content->formula as $f_xml){
			$f = new Formula(); $f->parse($f_xml);
			$this->formula[] = $f;
		}
	}
}

//IRIMETA? 'Exists' Var+ '(' FORMULA ')'
class Exists extends RifElement{
	public $var; // array
	public $formula;

	function parse($xml_content){
		//print_r($xml_content);
		$this->parseAnnotation($xml_content);		
		$this->var = array();
		foreach ($xml_content->declare as $declare){
			$v = new VarType(); $v->parse($declare->Var);
			$this->var[] = $v;								
		}
		
		$this->formula =  array();
		if ( isset ($xml_content->formula)){			
			$f = new Formula(); $f->parse($xml_content->formula);
			$this->formula = $f;								
		}
	}
}

class Atom extends UNITERM{
	function parse($xml_content){
		$this->parseAnnotation($xml_content);		
		//print_r($xml_content);
		parent::parse($xml_content);
		//print_r($this);
	}
}

//Equal          ::= TERM '=' TERM
class Equal extends RifElement{
	public $left; // Term
	public $right; // Term
	
	function parse($xml_content){
		$this->parseAnnotation($xml_content);		
		$this->left = new Term(); $this->left->parse($xml_content->left);
		$this->right = new Term(); $this->right->parse($xml_content->right);
	}
}

//Member         ::= TERM '#' TERM
class Member extends RifElement{
	public $instance; // Term
	public $class; // Term
	
	function parse($xml_content){
		$this->parseAnnotation($xml_content);		
		$this->instance = new Term(); $this->instance->parse($xml_content->instance);
		$this->class = new Term(); $this->class->parse($xml_content->class);
	}
}

//Frame          ::= TERM '[' (TERM '->' TERM)* ']'
class Frame extends RifElement{
	public $object;
	public $slot; // array of Slot

	function parse($xml_content){
		$this->parseAnnotation($xml_content);
		
		// object 
		$this->object = new Term(); 
		$this->object->parse($xml_content->object);

		// slot 
		$this->slot = array();
		foreach ($xml_content->slot as $slot)
		{		
			$c = $slot->children();
			$p0 = new Term(); $p0->parse($c[0]);
			$p1 = new Term(); $p1->parse($c[1]);
			$s = new Slot();
			$s->property = $p0; 
			$s->value = $p1;	
			
			$this->slot[] = $s;
		}		
	}
}

class Slot
{
	public $property; // Term
	public $value; // Term
}

?>