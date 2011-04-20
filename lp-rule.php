<?php
// Firsr-order Logic program rules
// e.g. man(X) :- male(X), human(X)

$LP_OP = array ('<','<=','>','>=','=','!=');

class LPAtom
{
	// must start with [a-z]
	public $predicate = null;	// e.g. human
	public $is_naf = false; // if has a negation as failure operatore, e.g. not p1(X)
	
	// An array of strings. Must start with [A-Z]
	public $variables = null;	// e.g., X,Y
	
	function LPAtom($p, $v, $naf=false)
	{
		$this->predicate = $p;
		$this->variables = $v;
		$this->is_naf = $naf;
	}
	
	function getArity()
	{
		if ($this->variables == null) return 0;
		else return count($this->variables);
	}
	
	function copy()
	{		
		$atom_new = @new LPAtom($this->predicate,
				$this->variables,
				$this->is_naf);
		return $atom_new;
	}
	
	function isOf($name)
	{
		if ($name == null)
			return false;
		else if ($this->predicate == null)
			return false;
		else
			return ($this->predicate == $name);		
	}
	
	function toString($unescape=false)
	{
		global $LP_OP;
		$name = $unescape? Rule::unesacpe($this->predicate) : $this->predicate ;
		// it it is of the form <, <=, >, >=, =
		if (in_array($name, $LP_OP) && count($this->variables) == 2){
			$r = $this->variables[0] . ' ' . $name . ' ' . $this->variables[1];
		}
		else{			
			$r = $name . "(" . implode("," , $this->variables) .	")";
		}
		if ($this->is_naf) $r = 'not ' . $r;
		return $r;
	}
}

class CountAtom extends LPAtom
{
	public $comparator;
	public $number;
	public $countVar;
	
	// so far, only one variable is accpeted
	function CountAtom($p, $v, $comparator, $number, $naf=false, $countVar)
	{
		$this->predicate = $p;
		$this->variables = $v;
		$this->comparator = $comparator;
		$this->number = $number;
		$this->is_naf = $naf;
		$this->countVar = $countVar;
	}
	
	function copy()
	{		
		$atom_new = @new CountAtom(
			$this->predicate,
			$this->variables,
			$this->comparator,
			$this->number,
			$this->is_naf,
			$this->countVar);
		return $atom_new;
	}	
	
	// e.g. #count{Y:p(X,Y)}<1 .
	function toString($unesacpe=false)
	{
		//println($this->countVar);
		$name = $unesacpe? Rule::unesacpe($this->predicate) : $this->predicate ;
		$vars = implode(",",$this->variables);
		return "#count{" . $this->countVar . ":" . $name . 
			"($vars)}$this->comparator$this->number";
	}
}

class Rule {	
	public $head = null; // a LPAtom
	public $body = null; // an array of LPAtom
	public static $DEBUG = false;

	function Rule($h, $b)
	{
		$this->head = $h;
		$this->body = $b;
	}
	
	static function escape($str, $replaceblank = true, $prefix = 'p')
	{
		//replace  non-alphanumeric characters with '_', since DLV only accept [A-Za-z0-9_]
		//return preg_replace('/\W/', '_', $str);
		if ($replaceblank){
			$str = preg_replace('/\s/', '_', $str);	
		}
		if (Rule::$DEBUG)
			return $str;
		else
			return $prefix . bin2hex($str);	
	}

	static function unesacpe($hex_str)
	{
		// if it is string - start with 's'
		$hex_str = trim($hex_str);
		
		// if it is dlvdb mode, the result is quoted, remove it
		$len = strlen($hex_str);
		if ($len >=2 && $hex_str[0] == "\"" || $hex_str[$len-1] == "\"")
			$hex_str = substr($hex_str,1,$len-2);
		
		// is it a number?
		if (is_numeric($hex_str[0])) return $hex_str;	
			
		// else,  an escaped string if starts with "p" or "s"
		//omit the first character
		if ($hex_str[0] == 'p' || $hex_str[0] == 's'){
			$hex_str = substr($hex_str,1);
			return Rule::hex2bin($hex_str);
		}
		return $hex_str;
	}
	
	static function hex2bin($hex_str)
	{
		if (!is_string($hex_str)) return null;
		$r='';
		for ($a=0; $a<strlen($hex_str); $a+=2) 
		{ 
			$r.=chr(hexdec($hex_str{$a}.$hex_str{($a+1)})); 
		}
		return $r;
	}
	
	static function makeLiteral($str)
	{
		return Rule::escape($str,false,"s");
	}
	
	static function makeNumber($str)
	{
		return preg_replace('/,/', '', $str);
	}

	// generate string representation of the rule
	function toString($unescape=false)
	{
		$s = "";
		
		// some rule may have no head, e.g., integrity constraint
		if ($this->head)
			$s .= $this->head->toString($unescape);			
			
		if ($this->body){			
			$s .= " :- " ;
			$body = "";
			foreach($this->body as $b)
				$body .= $b->toString($unescape) .", ";
			//remove the last  ", "	
			if (strlen($body)> 0 )
				$s .= substr ($body, 0,strlen($body)-2);			
		}
		
		if ($this->head || $this->body)
			$s .= " .";
		
		return $s;
	}
}

class LogicProgram
{
	public $rules = null ; // array of Rule
	
	public function LogicProgram($arr)
	{
		$this->rules = $arr;
	}
	
	public function addRule($rule)
	{
		if ($this->rules==null) 
			$this->rules = array($rule);
		else
			$this->rules[] = $rule;
	}
	
	public function addRule2($head, $body)
	{
		$this->addRule(new Rule($head,$body));
	}
	
	public function toString($unescape=false)
	{
		$s = "";
		foreach($this->rules as $r) {
			$s .= ($r->toString($unescape) . "\n");
		}
		return $s;
	}	
}
?>