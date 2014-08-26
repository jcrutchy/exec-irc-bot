<?php

# gpl2
# by crutchy
# 26-aug-2014

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
  "poops"=>"for",
  "blows"=>"at",
  "pours"=>"for",
  "flings"=>"at",
  "offers"=>"to",
  "tosses"=>"to",
  "postulates"=>"towards");
$containers=array(
  "a cold can",
  "a used franger",
  "a pair of used panties full",
  "a cheap plastic cup",
  "a wine flute",
  "a bathtub",
  "a spoon",
  "a blagoblag",
  "a tinfoil hat",
  "an assfull",
  "a bucket",
  "a wad",
  "an anvil",
  "a toilet bowl",
  "a coffee++ mug");
$beverages=array(
  "beer",
  "g'day juice",
  "coffee",
  "NCommander",
  "boogers",
  "bewb",
  "red cordial",
  "milo",
  "splodge",
  "skittles",
  "spew",
  "pancakes",
  "\$insert_beverage_here",
  "toilet water",
  "ciri poo",
  "bacon",
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

privmsg("* $nick $action1 $container of $beverage $action2 $trailing");

#####################################################################################################

?>
