<?php
function tab($level){
	return str_repeat("  ", $level);
}

function addLine($str, $level, $newline = true){
	return tab($level). $str . ($newline ? "\n" : "");
}

// add a new \n if the string does not end with \n
function addReturn ($str){
	if ($str[strlen($str)-1] == "\n") return $str;
	else return $str."\n";
}

function XMLToArrayFlat($xml, &$return, $path='', $root=false){ 
	$children = array(); 
	if ($xml instanceof SimpleXMLElement) { 
		$children = $xml->children(); 
		if ($root){ // we're at root 
			$path .= '/'.$xml->getName(); 
		} 
	} 
	if ( count($children) == 0 ){ 
		$return[$path] = (string)$xml; 
		return; 
	} 
	$seen=array(); 
	foreach ($children as $child => $value) { 
		$childname = ($child instanceof SimpleXMLElement)?$child->getName():$child; 
		if ( !isset($seen[$childname])){ 
			$seen[$childname]=0; 
		} 
		$seen[$childname]++; 
		XMLToArrayFlat($value, $return, $path.'/'.$child.'['.$seen[$childname].']'); 
	} 
}
?>