<?php

require_once("lp-rule.php");

class RuleFactory{
	private static $index = 0;
	private static $var_index = 0;
	
	static function makeAtom($name = null, $var_array, $isNaf = false)
	{
		if ($name == null) {
			$name = "tmp_" . RuleFactory::$index;
			RuleFactory::$index++;
		}
		return new LPAtom($name, $var_array, $isNaf);
	}
	
	static function getTempVar()
	{
		$name = "X" . RuleFactory::$var_index;
		RuleFactory::$var_index++;
		return $name;
	}
	
}
?>