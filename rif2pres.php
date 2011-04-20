<?php
// Jie Bao 2011-01-31
// RIF presentation syntax serializer 

require_once("../rif-rule.php");
require_once("../rif2pres-condition.php");


class Rif2Presentation extends Rif2PresentationCondition {
	public 		$writeAnnotation = true;
	
	//IRIMETA        ::= '(*' IRICONST? (Frame | 'And' '(' Frame* ')')? '*)'
	protected function writeAnnotation($annotation, $level)	{
		if (!$this->writeAnnotation) return;
		if (isset($annotation) && ($annotation instanceof Annotation))
		{
			//print_r($annotation->frame);
			$text = addLine("(* ", $level, false);
			if (isset($annotation->iri)){
				$text .= $this->writeConst($annotation->iri) . " ";
			}
			if (count($annotation->frame) > 0){
				if (count($annotation->frame) >1){
				    $text .= "And( ";
					foreach ($annotation->frame as $frame)
						$text .= addLine($this->writeFrame($frame), 0, false);
					$text .= " ) ";	
				}
				else if (count($annotation->frame) == 1){
					$text .= addLine($this->writeFrame($annotation->frame[0]), 0, false);
				}
			}
			$text .= addLine("*)", 0, false);
			return $text;
		}
	}
	
	// Implies        ::= IRIMETA? (ATOMIC | 'And' '(' ATOMIC* ')') ':-' FORMULA
	private function writeImplies($implies, $level){
		$text = '';
		if (isset($implies->annotation)){
			$text .= addReturn($this->writeAnnotation($implies->annotation, $level) );
		}
		$then = $this->writeFormula($implies->then, $level);
		if ($then[strlen($then)-1] == "\n") 
			$then[strlen($then)-1] = " ";
		$if_then = $then.
				   addLine(" :- ", 0 ) .
				   $this->writeFormula($implies->if, $level+1);
		$text .= addReturn($if_then);		
		return $text;
	}
	
	// CLAUSE         ::= Implies | ATOMIC
	private function writeClause($clause, $level){
		$text = '';
		switch($clause->type){
			case 'Implies':
				$text .= $this->writeImplies($clause->content, $level);
				break;
			case 'Atom':
				$text .= $this->writeAtom($clause->content, $level);
				break;
			case 'Frame':
				$text .= $this->writeFrame($clause->content, $level);
				break;
		}
		
		return $text;
	}
	
	//RULE           ::= (IRIMETA? 'Forall' Var+ '(' CLAUSE ')') | CLAUSE
	private function writeForall($forall, $level){
		$text = '';
		$var_text = '';
		if (isset($forall->annotation)){
			$text = addReturn($this->writeAnnotation($forall->annotation, $level));
		}
		foreach ($forall->var as $var){
			$var_text .= $this->writeVar($var, $level+1) ." ";
		}
		$text .= addLine("Forall $var_text(", $level); 
		// write the clause
		$text .= $this->writeClause($forall->clause, $level+1);
		$text .= addLine(")", $level);
		return $text;
	}
	
	//Group          ::= IRIMETA? 'Group' '(' (RULE | Group)* ')'
	private function writeGroup($group,$level){
		$text = '';
		if (isset($group->annotation)){
			$text = addReturn($this->writeAnnotation($group->annotation, $level));
		}
		$text .= addLine("Group(", $level);
		if (isset($group->sentences)){
			foreach ($group->sentences as $sentence){
				// each sentence may be a rule or a group
				switch (get_class($sentence)){
					case 'Forall':
					    $text .= $this->writeForall($sentence, $level+1);
						break;
					case 'Group':
						$text .= $this->writeGroup($sentence, $level+1);
						break;
					case 'Clause':
						$text .= $this->writeClause($sentence, $level+1);
						break;
				}		
				// if the last character is not "\n", add "\n";
				if ($text[strlen($text)-1] != "\n") $text .= "\n";	
			}	
		}
		$text .= addLine(")", $level);
		return $text;	
	}
	
	// $imports is the array of Import
	private function writeImport($imports, $level)
	{
		if (count($imports) == 0) return;
		$text = "\n";
		foreach ($imports as $imp){
			if (isset($imp->annotation)){
				$text .= addReturn ($this->writeAnnotation($imp->annotation, $level));
			}
			$profile ='';
			if (isset($imp->profile)) $profile = " <$imp->profile>";
			$text .= addLine("Import(<$imp->locator>$profile)" , $level);
		}
		return $text;
	}
	
	// Base           ::= 'Base' '(' ANGLEBRACKIRI ')'
	private function writeBase($base, $level){
		if (isset($base))
			return addLine("Base($base)" , $level);
		else return '';	
	}
	
	// Prefix         ::= 'Prefix' '(' NCName ANGLEBRACKIRI ')'
	private function writePrefix($prefix, $level){
		//print_r($prefix);		
		$text= "";
		if (isset($group->annotation)){
			$text .= addReturn($this->writeAnnotation($prefix->annotation, $level));
		}
		if (isset($prefix)){
			foreach ($prefix as $short=>$full){
				if ($short == ""){
					//$text .= addLine("Base(<$full>)" , $level);
					// just skip
				}else
					$text .= addLine("Prefix($short <$full>)" , $level);
			}
		}	
		return $text;
	}

	// Document       ::= IRIMETA? 'Document' '(' Base? Prefix* Import* Group? ')'
	private function writeDocument($doc, $level)	{		
		$text = '';
		// annotation
		if (isset($doc->annotation)){
			$text = $this->writeAnnotation($doc->annotation, $level);
		}
		
		$g_text = '';
		foreach ($doc->group as $group){
			$g_text .= $this->writeGroup($group, $level+1);		
		}	
		
		$text .= addLine("\nDocument(",$level) . 
				 $this->writeBase($doc->base, $level+1) .
				 $this->writePrefix($doc->prefix, $level+1) .
				 $this->writeImport($doc->imports, $level+1) .
				 addLine(" ", $level) .
				 $g_text .  // groups
				 // something else
				 addLine(")", $level);
		return $text;
	}

	public function toPresentationSyntax($doc)	{
		if ($doc instanceof RifDocument) {
			$this->doc = $doc;
			return 	$this->writeDocument($doc, 0);		
		}
	}
}
?>