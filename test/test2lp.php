<?php
require_once("../rif2lp.php");
function newConst(){
	$t = new ConstType();
	$t->type = 'http://www.w3.org/2007/rif#local';
	$t->content = 'str';	
	return $t;
}

function newVar(){
	$t = new VarType();
	$t->name = 'x';
	return $t;
}

function newList(){
	$t = new ListType();
	$term1 = new Term(); $term1->type = 'Const';  $term1->content = newConst();
	$term2 = new Term(); $term2->type = 'Var';  $term1->content = newVar();
	$t->items = array($term1,$term2);
}


function test(){
	$writer = new Rif2LP();
	
	$t = newList();
	echo $writer->writeList($t);
}

test();
?>