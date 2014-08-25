<?php

# gpl2
# by crutchy
# 24-aug-2014

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);

if ($trailing=="")
{
  return;
}

$actions=array(
  "cracks open"=>"for",
  "passes"=>"to",
  "throws"=>"at",
  "slides"=>"to",
  "hurls"=>"at",
  "flings"=>"at",
  "offers"=>"to",
  "tosses"=>"to",
  "postulates"=>"towards");
$containers=array(
  "cold can",
  "used franger",
  "pair of used panties full",
  "cheap plastic cup",
  "wine flute",
  "bathtub",
  "bucket",
  "wad",
  "toilet bowl",
  "coffee++ mug");
$beverages=array(
  "beer",
  "g'day juice",
  "coffee",
  "NCommander",
  "testicle juice",
  "bewb juice",
  "red cordial",
  "milo",
  "toilet water",
  "ciri poo",
  "Soylent Green");

$last_action=get_bucket("<<GDAY_LAST_ACTION>>");
$last_container=get_bucket("<<GDAY_LAST_CONTAINER>>");
$last_beverage=get_bucket("<<GDAY_LAST_BEVERAGE>>");

$action_keys=array_keys($actions);
do
{
  $action1=$action_keys[rand(0,count($action_keys)-1)];
  $action2=$actions[$action1];
  $container=$containers[rand(0,count($containers)-1)];
  $beverage=$beverages[rand(0,count($beverages)-1)];
}
while (($action1==$last_action) or ($container==$last_container) or ($beverage==$last_beverage));

set_bucket("<<GDAY_LAST_ACTION>>",$action1);
set_bucket("<<GDAY_LAST_CONTAINER>>",$container);
set_bucket("<<GDAY_LAST_BEVERAGE>>",$beverage);

privmsg("* $nick $action1 a $container of $beverage $action2 $trailing");

#####################################################################################################

?>
