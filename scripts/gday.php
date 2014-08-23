<?php

# gpl2
# by crutchy
# 24-aug-2014

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);

$actions=array("cracks open"=>"for","passes"=>"to","throws"=>"at","slides"=>"to","hurls"=>"at","flings"=>"at","offers"=>"to","postulates"=>"towards");
$containers=array("cold can","used franger","pair of used panties full","cheap plastic cup","wine flute","bathtub","bucket","toilet bowl","coffee++ mug");
$beverages=array("beer","g'day juice","coffee","NCommander","testicle juice","bewb juice","red cordial","milo","toilet water","ciri poo","Soylent Green");

$action_keys=array_keys($actions);
$action1=$action_keys[mt_rand(0,count($action_keys)-1)];
$action2=$actions[$action1];
$container=$containers[mt_rand(0,count($containers)-1)];
$beverage=$beverages[mt_rand(0,count($beverages)-1)];

if ($trailing<>"")
{
  privmsg("* $nick $action1 a $container of $beverage $action2 $trailing");
}

#####################################################################################################

?>
