<?php

$xmlstr = file_get_contents("ex8.rif");
$pattern = "/<Document.*?\sxml:base\s*=\s*\"(.*?)\".*?>/sm";
preg_match($pattern, $xmlstr, $matches);
print_r($matches);  
  
?> 