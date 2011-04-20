<?php
// Jie Bao 2011-01-31
// serializer of presentation syntax for the condition language 

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
  UNITERM        ::= Const '(' (TERM* ')'
  GROUNDUNITERM  ::= Const '(' GROUNDTERM* ')'
  Equal          ::= TERM '=' TERM
  Member         ::= TERM '#' TERM
  Frame          ::= TERM '[' (TERM '->' TERM)* ']'
  TERM           ::= IRIMETA? (Const | Var | List | 'External' '(' Expr ')')
  GROUNDTERM     ::= IRIMETA? (Const | List | 'External' '(' GROUNDUNITERM ')')
  Expr           ::= UNITERM
  List           ::= 'List' '(' GROUNDTERM* ')'
  Const          ::= '"' UNICODESTRING '"^^' SYMSPACE | CONSTSHORT
  Var            ::= '?' Name
  Name           ::= NCName | '"' UNICODESTRING '"'
  SYMSPACE       ::= ANGLEBRACKIRI | CURIE
*/

require_once("helper.php");

abstract class Rif2PresentationCondition{
	public 		$useCurieURL = true;
	protected 	$doc;
		
	/*
	FORMULA      ::= IRIMETA? 'And' '(' FORMULA* ')' |
                     IRIMETA? 'Or' '(' FORMULA* ')' |
                     IRIMETA? 'Exists' Var+ '(' FORMULA ')' |
                     ATOMIC |
                     IRIMETA? Equal |
                     IRIMETA? Member |
                     IRIMETA? 'External' '(' Atom ')'
	*/
	protected function writeFormula($formula, $level = 0){
		$text = '';
		switch($formula->type){
			case 'Atom':
			    $text = $this->writeAtom($formula->content, $level);
				break;
			case 'Frame':
			    $text = $this->writeFrame($formula->content, $level);
				break;
			case 'And':
				$text  = addLine('And(', $level); //AndFormula
				foreach($formula->content->formula as $item){
					$text .= addReturn($this->writeFormula($item, $level+1));
				}
				$text .= addLine(')', $level); 
				break;
			case 'Or':
				$text  = addLine('Or(', $level); //OrFormula
				foreach($formula->content->formula as $item){
					$text .= addReturn($this->writeFormula($item, $level+1));
				}
				$text .= addLine(')', $level); 
				break;
			case 'Exists':
				$text = $this->writeExists($formula->content, $level);
				break;				
			case 'Equal':
				$text = $this->writeEqual($formula->content, $level);
				break;	
			case 'External':
				$text = $this->writeExternal($formula->content, $level);
				break;	
			case 'Member':
				$text = $this->writeMember($formula->content, $level);
		}		
		return $text;
	}
	
	//  Equal   ::= TERM '=' TERM
	protected function writeEqual($equal, $level){
	    $text = "";
		if (isset($equal->annotation)){
			$text = addReturn($this->writeAnnotation($equal->annotation, $level) );
		}
		$text .= tab($level). $this->writeTerm($equal->left) . " = " . 
				$this->writeTerm($equal->right);
		$text = addReturn($text);
		return $text;
	}
	
	//Member         ::= TERM '#' TERM
	protected function writeMember($member, $level){
		$text = "";
		if (isset($member->annotation)){
			$text = addReturn($this->writeAnnotation($member->annotation, $level) );
		}
		$text .= tab($level). $this->writeTerm($member->instance) . " # " . 
				$this->writeTerm($member->class);
		$text = addReturn($text);
		return $text;
	}

	protected function writeExists($exists, $level){
		$text = '';
		$var_text = '';
		if (isset($exists->annotation)){
			$text = addReturn ($this->writeAnnotation($exists->annotation, $level));
		}
		foreach ($exists->var as $var){
			$var_text .= $this->writeVar($var, $level+1) ." ";
		}
		$text .= addLine("Exists $var_text(", $level); 
		// write the clause
		$text .= $this->writeFormula($exists->formula, $level+1);
		$text .= addLine(")", $level);
		return $text;
	}

	//	Atom           ::= UNITERM
	//  UNITERM        ::= Const '(' (TERM* ')'
	protected function writeAtom($atom, $level = 0){
		$text = addLine($this->writeUNITERM($atom), $level, false);
		return $text;
	}

	protected function writeUNITERM($uniterm){
		$text = $this->writeConst($uniterm->op) . ' ( ';
		foreach ($uniterm->arg as $arg){
			$text .= $this->writeTerm($arg) ." ";
		}
		$text .= ') ';
		return $text;
	}
	
	//Const          ::= '"' UNICODESTRING '"^^' SYMSPACE | CONSTSHORT
    //SYMSPACE       ::= ANGLEBRACKIRI | CURIE
    protected function writeConst($const){
		$rifiri = 'http://www.w3.org/2007/rif#iri';
		$riflocal = 'http://www.w3.org/2007/rif#local';
				
		// if type is 'rif:iri', then just write the iri part
		if ($const->type == $rifiri)
			$text = $this->writeCurie($const->content);
		//else if ($const->type == $riflocal)
		//	$text = '_'.$const->content;  //@todo - not sure if a rif:local can have blank charecters.
		else	
			$text = $this->writeString($const->content) . "^^" . $this->writeCurie($const->type);
		return $text;
	}	

	protected function writeString($str)	{
		return "\"$str\"";
	}	

	protected function writeCurie($iri)	{
		if ($this->useCurieURL && isset($this->doc)){
			if (isset($this->doc->prefix)){
				$p= $this->doc->prefix;
				// find the short prefix for the url
				foreach ($p as $short => $full){
					//print (" $short $full\n");
					$pos = strpos($iri, $full);	
					if ($pos !== false){	
						if (($short != "") && ($pos === 0)){						
							//print (" -- $short $full $iri\n");
							return $short . ":" . substr($iri, strlen($full));
						}
					}
				}
			}			
		}
		return $this->writeIRI($iri);
	}
	
	protected function writeIRI($iri){
		return "<$iri>";
	}	
	
	//TERM           ::= IRIMETA? (Const | Var | List | 'External' '(' Expr ')')
	protected function writeTerm($term)	{
		//print_r($term);
		$text = $term->type;
		switch ($term->type)		
		{
			case 'Const':				
				$text = $this->writeConst($term->content);				
				break;
			case 'Var':
				$text = $this->writeVar($term->content,0);
				break;
			case 'List':
				$text = $this->writeList($term->content);
				break;
			case 'External':
				$text = $this->writeExternal($term->content,0);				
				break;
		}
		return $text;
	}
	
	protected function writeList($list){
	    $text = '';
		if (isset($list->annotation)){
			$text = $this->writeAnnotation($list->annotation,0);
		}
		$text .= 'List( ';
		foreach($list->items as $item){
			$text .= $this->writeTerm($item) . " ";
		}
		$text .= ')';
		return $text;
	}
	
	protected function writeVar($var, $level){
		//print_r($var);
		$text = '';
		if (isset($var->annotation)){
			$text = $this->writeAnnotation($var->annotation, $level+1);
		}
		$text .= "?$var->name";
		return $text;
	}
	
	//'External' '(' Expr ')')	
	// Expr           ::= UNITERM
  	// UNITERM        ::= Const '(' (TERM* ')'
	protected function writeExternal($external, $level = 0){
		$text  = tab($level).'External(';
		$text .= $this->writeUNITERM($external);
		$text .= ') ';
		return $text;
	}
	
	// Frame          ::= TERM '[' (TERM '->' TERM)* ']'
	protected function writeFrame($frame, $level = 0){
		if (isset($frame->annotation)){
			$text = addReturn ($this->writeAnnotation($frame->annotation, $level));
		}
		// Frame has object and slot (an array of Slots) 
		$text  = tab($level) . $this->writeTerm($frame->object) . " [" ;
		foreach ($frame->slot as $slot){
			$text .= $this->writeTerm($slot->property) .
					 "->".
			         $this->writeTerm($slot->value) . " ";			
		}
		$text .= "] ";
		return $text;
	}}
?>