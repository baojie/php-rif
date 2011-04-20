<?php
error_reporting(E_ALL);
date_default_timezone_set('America/New_York');

require_once("../rif2pres.php");
require_once("../rif2lp.php");

function testRIFCore()
{
	echo "Test RIF Core data structure\n\n";
	
	// parse XML syntax
	$rifdoc = new RifDocument();
	$rifdoc->load("ex8.rif");
	
	// write in presentation syntax
	//$writer = new Rif2Presentation();
	//$writer->writeAnnotation = true;
	//$syntax = $writer->toPresentationSyntax($rifdoc);
	//echo $syntax;

	$writer = new Rif2LP();
	$writer->toLP($rifdoc);
	//print_r($writer->getLP());
	echo $writer->getLP()->toString();

}

testRIFCore();

?>