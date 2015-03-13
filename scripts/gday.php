<?php

#####################################################################################################

/*
exec:~g'day|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
exec:~gday|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
exec:~g'night|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
exec:~gnight|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);

if ($trailing=="")
{
  return;
}

$adverbs=array(
  "brazenly",
  "spontaneously",
  "prematurely",
  "unjustifiably",
  "insatiably",
  "abnormally",
  "abrasively",
  "accidentally",
  "allegedly",
  "clumsily",
  "cohesively",
  "covertly",
  "dexterously",
  "diabolically",
  "fanatically",
  "suspiciously");
$actions=array(
  "cracks open"=>"for",
  "passes"=>"to",
  "throws"=>"at",
  "slides"=>"to",
  "hurls"=>"at",
  "poops"=>"for",
  "drops"=>"on",
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
  "a socket",
  "a buzz saw",
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
  "milo",
  "boogers",
  "bewb",
  "red cordial",
  "splodge",
  "skittles",
  "vibrating rooster sammich",
  "glowballs",
  "spew",
  "pancakes",
  "\$insert_beverage_here",
  "toilet water",
  "ciri poo",
  "bacon",
  "Debian",
  "coffee++",
  "Soylent Green");

$last_adverb=get_bucket("<<GDAY_LAST_ADVERB>>");
$last_action=get_bucket("<<GDAY_LAST_ACTION>>");
$last_container=get_bucket("<<GDAY_LAST_CONTAINER>>");
$last_beverage=get_bucket("<<GDAY_LAST_BEVERAGE>>");

$action_keys=array_keys($actions);

do
{
  $adverb=$adverbs[rand(0,count($adverbs)-1)];
}
while ($adverb==$last_adverb);
do
{
  $action1=$action_keys[rand(0,count($action_keys)-1)];
}
while ($action1==$last_action);
do
{
  $container=$containers[rand(0,count($containers)-1)];
}
while ($container==$last_container);
do
{
  $beverage=$beverages[rand(0,count($beverages)-1)];
}
while ($beverage==$last_beverage);

$action2=$actions[$action1];

set_bucket("<<GDAY_LAST_ADVERB>>",$adverb);
set_bucket("<<GDAY_LAST_ACTION>>",$action1);
set_bucket("<<GDAY_LAST_CONTAINER>>",$container);
set_bucket("<<GDAY_LAST_BEVERAGE>>",$beverage);

privmsg(chr(1)."ACTION $adverb $action1 $container of $beverage $action2 $trailing".chr(1));

#####################################################################################################

?>
