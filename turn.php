<?php

# gpl2
# by crutchy
# 18-april-2014

# 5|0|0|turn|php turn.php %%nick%% %%msg%%

# players file: nick|x|y

ini_set("display_errors","on");

define("GAME_NAME","turn");

term_echo("running");

if ((isset($argv[1])==False) or (isset($argv[2])==False) or (isset($argv[3])==False))
{
  privmsg("exec error");
  return;
}

$alias=$argv[1];
$nick=$argv[2];
$msg=$argv[3];

if (($msg=="") and ($alias==GAME_NAME))
{
  privmsg("usage: turn [up|down|left|right]");
  privmsg("       turn-add");
  return;
}

$game=file_get_contents("game");
$map=file_get_contents("map");
$players=file_get_contents("players");

if (($game===False) or ($map===False) or ($players===False))
{
  privmsg("error reading one or more files");
  return;
}

$lines=explode("\n",$players);
$players=array();
$n=count($lines);
for ($i=0;$i<$n;$i++)
{
  $line=trim($lines[$i]);
  if ($line=="")
  {
    continue;
  }
  if (substr($line,0,1)=="#")
  {
    continue;
  }
  $parts=explode("|",$line);
  if (count($parts)<>3)
  {
    continue;
  }
  $player_nick=$parts[0];
  $player_x=$parts[1];
  $player_y=$parts[2];
  $players[$player_nick]["x"]=$player_x;
  $players[$player_nick]["y"]=$player_y;
}

if ($alias==(GAME_NAME."-add"))
{
  if (isset($players[$nick])==False)
  {
    $players[$nick]["x"]=0;
    $players[$nick]["y"]=0;
    privmsg("player \"$nick\" added");
  }
  else
  {
    privmsg("player nick already exists");
    return;
  }
}

if (count($players)==0)
{
  privmsg("no players found");
  return;
}

if (isset($players[$nick])==False)
{
  privmsg("player \"$nick\" not found in player file");
  return;
}

$params=explode(" ",$msg);
if (count($params)==1)
{
  switch (strtolower($params[0]))
  {
    case "up":
      $players[$nick]["y"]=$players[$nick]["y"]+1;
      break;
    case "left":
      $players[$nick]["x"]=$players[$nick]["x"]-1;
      break;
    case "right":
      $players[$nick]["x"]=$players[$nick]["x"]+1;
      break;
    case "down":
      $players[$nick]["y"]=$players[$nick]["y"]-1;
      break;
    default:
      privmsg("unknown command");
      return;
  }
}
else
{
  privmsg("insufficient params");
  return;
}

$data="";
foreach ($players as $player_nick => $player_data)
{
  if ($data<>"")
  {
    $data=$data."\n";
  }
  $data=$data.$player_nick."|".$player_data["x"]."|".$player_data["y"];
}

if (file_put_contents("players",$data)===False)
{
  privmsg("error saving players file");
  return;
}

privmsg($nick.": (".$players[$nick]["x"].",".$players[$nick]["y"].")");

return;

function term_echo($msg)
{
  echo GAME_NAME.": $msg\n";
}

function privmsg($msg)
{
  echo "privmsg ".GAME_NAME.": $msg\n";
}

?>
