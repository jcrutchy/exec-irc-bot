<?php

#####################################################################################################

/*
exec:~g'day|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
exec:~gday|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
exec:~g'night|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
exec:~gnight|5|0|0|1|||||php scripts/gday.php %%trailing%% %%nick%%
*/

#####################################################################################################

ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);
date_default_timezone_set("UTC");

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);

if ($trailing=="")
{
  return;
}

$fn=DATA_PATH."gday_data";
if (file_exists($fn)==True)
{
  $data=json_decode(file_get_contents($fn),True);
}
else
{
  $data=array();
  $data["adverbs"]=array(
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
  $data["actions"]=array(
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
  $data["containers"]=array(
    "a cold can",
    "a used franger",
    "a pair of used panties full",
    "a cheap plastic cup",
    "a wine flute",
    "a bathtub",
    "a spoon",
    "a socket",
    "a caravan",
    "a buzz saw",
    "a blagoblag",
    "a DD cup",
    "a tinfoil hat",
    "an assfull",
    "a bucket",
    "a wad",
    "an anvil",
    "a toilet bowl",
    "a coffee++ mug");
  $data["beverages"]=array(
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
    "dag",
    "Debian",
    "coffee++",
    "Soylent Green");
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$parts=array_values($parts);
$arg=implode(" ",$parts);
$save_data=False;
switch ($action)
{
  case ">adverb":
    if (in_array($arg,$data["adverbs"])==False)
    {
      $data["adverbs"][]=$arg;
      privmsg("added to adverbs");
      $save_data=True;
    }
    else
    {
      privmsg("error: adverb already exists");
      return;
    }
    break;
  case "<adverb":
    $index=array_search($arg,$data["adverbs"],True);
    if ($index!==False)
    {
      unset($data["adverbs"][$index]);
      $data["adverbs"]=array_values($data["adverbs"]);
      $save_data=True;
      privmsg("deleted adverb");
    }
    else
    {
      privmsg("error: adverb not found");
      return;
    }
    break;
  case ">action":
    $action2=array_pop($parts);
    $action1=implode(" ",$parts);
    if (isset($data["actions"][$action1])===False)
    {
      $data["actions"][$action1]=$action2;
      privmsg("added to actions");
      $save_data=True;
    }
    else
    {
      privmsg("error: action already exists");
      return;
    }
    break;
  case "<action":
    if (isset($data["actions"][$arg])===True)
    {
      unset($data["actions"][$arg]);
      $save_data=True;
      privmsg("deleted action");
    }
    else
    {
      privmsg("error: action not found");
      return;
    }
    break;
  case ">container":
    if (in_array($arg,$data["containers"])==False)
    {
      $data["containers"][]=$arg;
      privmsg("added to containers");
      $save_data=True;
    }
    else
    {
      privmsg("error: container already exists");
      return;
    }
    break;
  case "<container":
    $index=array_search($arg,$data["containers"],True);
    if ($index!==False)
    {
      unset($data["containers"][$index]);
      $data["containers"]=array_values($data["containers"]);
      $save_data=True;
      privmsg("deleted container");
    }
    else
    {
      privmsg("error: container not found");
      return;
    }
    break;
  case ">beverage":
    if (in_array($arg,$data["beverages"])==False)
    {
      $data["beverages"][]=$arg;
      privmsg("added to beverages");
      $save_data=True;
    }
    else
    {
      privmsg("error: beverage already exists");
      return;
    }
    break;
  case "<beverage":
    $index=array_search($arg,$data["beverages"],True);
    if ($index!==False)
    {
      unset($data["beverages"][$index]);
      $data["beverages"]=array_values($data["beverages"]);
      $save_data=True;
      privmsg("deleted beverage");
    }
    else
    {
      privmsg("error: beverage not found");
      return;
    }
    break;
  case "<list>":
    output_ixio_paste(file_get_contents($fn));
    return;
}
if ($save_data==True)
{
  if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
  {
    privmsg("error writing data file");
  }
  return;
}

$adverbs=$data["adverbs"];
$actions=$data["actions"];
$containers=$data["containers"];
$beverages=$data["beverages"];

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

$parts=explode(" ",$trailing);
$target=$parts[0];

privmsg(chr(1)."ACTION $adverb $action1 $container of $beverage $action2 $target".chr(1));

#####################################################################################################

?>
