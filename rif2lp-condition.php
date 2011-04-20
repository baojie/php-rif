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
require_once("rif-rule-factory.php");

abstract class Rif2LPCondition{
	public 		$useCurieURL = true;
	public 	$doc;	// the parsed rif object
	public   $lp;	// the translated datalog
		
	public function getLP(){
		return $this->lp;
	}
	
	public function getVar($formula){
		$var = array();
		switch($formula->type){
			case 'Atom':
				$atom  = $formula->content;
				foreach ($atom->arg as $term){
					if ($term->type == 'Var') 
						$var[] = $this->writeVar($term->content);
				}	    
				break;
			case 'Frame':
			    //$text = $this->writeFrame($formula->content);
				break;
			case 'And':				    
				foreach($formula->content->formula as $item){
					$var = array_merge($var, $this->getVar($item));
				}
				break;
			case 'Or':
				//foreach($formula->content->formula as $item){
				//	$text .= $this->writeFormula($item);
				//}
				break;
			case 'Exists':
				//$text = $this->writeExists($formula->content);
				break;				
			case 'Equal':
				//$text = $this->writeEqual($formula->content);
				break;	
			case 'External':
				//$text = $this->writeExternal($formula->content);
				break;	
			case 'Member':
				//$text = $this->writeMember($formula->content);
		}		
		return $var;
	}
		
	/*
	FORMULA      ::= IRIMETA? 'And' '(' FORMULA* ')' |
                     IRIMETA? 'Or' '(' FORMULA* ')' |
                     IRIMETA? 'Exists' Var+ '(' FORMULA ')' |
                     ATOMIC |
                     IRIMETA? Equal |
                     IRIMETA? Member |
                     IRIMETA? 'External' '(' Atom ')'
	*/
	// head is a LPAtom
	// result: add some new rules to $this->lp
	public function writeFormula($formula, $head){
	    $text ='';
		switch($formula->type){
			case 'Atom':
				$body = $this->writeAtom($formula->content);
				$this->lp->addRule2($head, array($body));			    
				break;
			case 'Frame':
			    //$text = $this->writeFrame($formula->content);
				break;
			case 'And':	
			    $var = $this->getVar($formula);				
				$head = RuleFactory::makeAtom(null,$var);
				$body = array();
				foreach($formula->content->formula as $item){
					$body[] =    $this->writeFormula($item, $head);
					$this->lp->addRule2($a, array($head));
				}
				$this->lp->addRule2($head, $body);
				break;
			case 'Or':
				$text  = 'Or('; //OrFormula
				foreach($formula->content->formula as $item){
					//$text .= $this->writeFormula($item);
				}
				$text .= ')'; 
				break;
			case 'Exists':
				//$text = $this->writeExists($formula->content);
				break;				
			case 'Equal':
				//$text = $this->writeEqual($formula->content);
				break;	
			case 'External':
				//$text = $this->writeExternal($formula->content);
				break;	
			case 'Member':
				//$text = $this->writeMember($formula->content);
		}		
		return $text;
	}
	
	// Equal   ::= TERM '=' TERM
	// return: LPAtom
	public function writeEqual($equal){
		return RuleFactory::makeAtom( "=",
			array($this->writeTerm($equal->left), 
				$this->writeTerm($equal->right)));		
	}
	
	// Member         ::= TERM '#' TERM
	// return: LPAtom
	public function writeMember($member){
		return RuleFactory::makeAtom( $this->writeTerm($member->class), 
				array($this->writeTerm($member->instance)));

	}

	//	Atom           ::= UNITERM
	//  UNITERM        ::= Const '(' TERM* ')'
	// return LPAtom
	public function writeAtom($atom, $level = 0){
		return $this->writeUNITERM($atom);
	}

	// return LPAtom
	public function writeUNITERM($uniterm){
		$op = $this->writeConst($uniterm->op);
		$v = array();
		foreach ($uniterm->arg as $arg){
			$v[] = $this->writeTerm($arg);
		}
		return RuleFactory::makeAtom($op,$v);
	}
	
	//Const          ::= '"' UNICODESTRING '"^^' SYMSPACE | CONSTSHORT
    //SYMSPACE       ::= ANGLEBRACKIRI | CURIE
	// return: string
    public function writeConst($const){
		$rifiri = 'http://www.w3.org/2007/rif#iri';
		$riflocal = 'http://www.w3.org/2007/rif#local';
		
		// if type is 'rif:iri', then just write the iri part
		if ($const->type == $rifiri)
			$text = $this->writeCurie($const->content);
		else if ($const->type == $riflocal)
			$text = $this->writeString($const->content) ;  
		else	
			$text = $this->writeString($const->content) . "^^" . $this->writeCurie($const->type);
		return $text;
	}	

	public function writeString($str)	{
		return "\"$str\"";
	}	

	public function writeCurie($iri)	{
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
	
	public function writeIRI($iri){
		return "<$iri>";
	}	
	
	//TERM           ::= IRIMETA? (Const | Var | List | 'External' '(' Expr ')')
	// return: string
	public function writeTerm($term)	{
		//print_r($term);
		$text = $term->type;
		switch ($term->type)		
		{
			case 'Const':				
				$text = $this->writeConst($term->content);				
				break;
			case 'Var':
				$text = $this->writeVar($term->content);
				break;
			case 'List':
				$text = $this->writeList($term->content);
				break;
			case 'External':
				$text = $this->writeExternal($term->content);				
				break;
		}
		return $text;
	}
	
	public function writeList($list){
	    $text = 'List( ';
		foreach($list->items as $item){
			$text .= $this->writeTerm($item) . " ";
		}
		$text .= ')';
		return $text;
	}
	
	public function writeVar($var){
		$text = "?$var->name";
		return $text;
	}
	
	//'External' '(' Expr ')')	
	// Expr           ::= UNITERM
  	// UNITERM        ::= Const '(' (TERM* ')'
	public function writeExternal($external){
		$text  = 'External(';
		$text .= $this->writeUNITERM($external);
		$text .= ') ';
		return $text;
	}
	
	// Frame          ::= TERM '[' (TERM '->' TERM)* ']'
	// return: Array of LPAtom
	public function writeFrame($frame){
	    $atoms = array();
		foreach ($frame->slot as $slot){
			$op = $this->writeTerm($slot->property) .
			$v  = array($this->writeTerm($frame->object),
			         $this->writeTerm($slot->value) );	
			$atoms[] = RuleFactory::makeAtom($op,$v);			
		}
		return $atoms;
	}}
?>