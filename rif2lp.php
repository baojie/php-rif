<?php
// Jie Bao 2011-01-31
// RIF to LP translator 

require_once("rif-rule.php");
require_once("rif2lp-condition.php");
require_once("lp-rule.php");

class Rif2LP extends Rif2LPCondition {	

	// Implies        ::= IRIMETA? (ATOMIC | 'And' '(' ATOMIC* ')') ':-' FORMULA
	private function writeImplies($implies){	
		//then part
		$var = $this->getVar($implies->then);
		//print_r($implies->then);
		switch($implies->then->type){
			case 'Atom':
				$then = $this->writeAtom($implies->then->content);
				break;
			case 'And':			    
				$then = RuleFactory::makeAtom(null,$var);
				foreach($implies->then->content->formula as $item){
					$a = $this->writeAtom($item->content);
					$this->lp->addRule2($a, array($then));
				}				
				break;
		}
		$if   = RuleFactory::makeAtom(null,$var);
		$this->writeFormula($implies->if, $if);
		$this->lp->addRule2($then, array($if));
	}	
	
	// CLAUSE         ::= Implies | ATOMIC
	// Implies, add new rules
	// Atom or Frame: return array of LPAtom
	private function writeClause($clause){		
		switch($clause->type){
			case 'Implies':
				$this->writeImplies($clause->content);
				break;
			// the next cases are ground facts	
			case 'Atom':
				return array($this->writeAtom($clause->content));
				break;
			case 'Frame':
				return $this->writeFrame($clause->content);
				break;
		}		
	}
	
	//RULE           ::= (IRIMETA? 'Forall' Var+ '(' CLAUSE ')') | CLAUSE
	private function writeForall($forall){
		$this->writeClause($forall->clause);
	}
	
	//Group          ::= IRIMETA? 'Group' '(' (RULE | Group)* ')'
	private function writeGroup($group){		
		if (isset($group->sentences)){
			foreach ($group->sentences as $sentence){
				// each sentence may be a rule or a group
				switch (get_class($sentence)){
					case 'Forall':
					    $this->writeForall($sentence);
						break;
					case 'Group':
						$this->writeGroup($sentence);
						break;
					case 'Clause':
						$this->writeClause($sentence);
						break;
				}		
			}	
		}
	}
	
	// Document       ::= IRIMETA? 'Document' '(' Base? Prefix* Import* Group? ')'
	private function writeDocument($doc)	{		
		foreach ($doc->group as $group){
			$this->writeGroup($group);		
		}		
	}

	public function toLP($doc)	{
		if ($doc instanceof RifDocument) {
			$this->doc = $doc;
			$this->lp = new LogicProgram(array());
			$this->writeDocument($doc);		
		}
	}
}
?>