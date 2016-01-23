<?php

#####################################################################################################
################################### ROCK / PAPER / SCISSORS GAME ####################################
#####################################################################################################

/*
exec:~rps|10|0|0|1|*|PRIVMSG|||php scripts/rps.php %%trailing%% %%dest%% %%nick%% %%alias%% %%params%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$params=$argv[5];

$data=get_array_bucket("<<EXEC_RPS_DATA>>");

if (valid_rps_sequence($trailing)==True)
{
  $account=users_get_account($nick);
  if ($account=="")
  {
    return;
  }
  if (isset($data["rounds"])==False)
  {
    $data["rounds"]=1;
  }
  if (isset($data["users"][$account]["sequence"])==False)
  {
    $data["users"][$account]["sequence"]="";
  }
  if (strlen($data["users"][$account]["sequence"].$trailing)>($data["rounds"]+1))
  {
    $trailing=substr($trailing,0,$data["rounds"]-strlen($data["users"][$account]["sequence"])+1);
    privmsg("additional sequence trimmed to: $trailing");
  }
  if (isset($data["users"])==False)
  {
    $data["users"]=array();
  }
  if (isset($data["users"][$account])==False)
  {
    $data["users"][$account]=array();
    $data["users"][$account]["rank"]="ERROR";
  }
  $ts=microtime(True);
  if (isset($data["users"][$account]["timestamp"])==True)
  {
    if (($ts-$data["users"][$account]["timestamp"])<mt_rand(3,8))
    {
      privmsg("please wait a few seconds before trying again");
      return;
    }
  }
  $data["users"][$account]["timestamp"]=$ts;
  $data["users"][$account]["sequence"]=$data["users"][$account]["sequence"].$trailing;
  $data["rounds"]=max($data["rounds"],strlen($data["users"][$account]["sequence"]));
  set_array_bucket($data,"<<EXEC_RPS_DATA>>");
  $ranks=update_ranking($data);
  privmsg("rank for $account: ".$data["users"][$account]["rank"]);
  output_ixio_paste($ranks);
  return;
}

if ($trailing=="ranks")
{
  output_ixio_paste(get_ranking($data));
  return;
}

privmsg("syntax: ~rps [ranks|r|p|s]");

#####################################################################################################

function valid_rps_sequence($trailing)
{
  for ($i=0;$i<strlen($trailing);$i++)
  {
    switch ($trailing[$i])
    {
      case "r":
      case "p":
      case "s":
        continue;
      default:
        return False;
    }
  }
  return True;
}

#####################################################################################################

/*
r > s
s > p
p > r
r = r
p = p
s = s
*/

function update_ranking(&$data)
{
  $out="rankings after ".$data["rounds"]." rounds:\n\n";
  foreach ($data["users"] as $account => $user_data)
  {
    for ($i=0;$i<strlen($data["users"][$account]["sequence"]);$i++)
    {
      
    }
    $data["users"][$account]["rank"]=0;
    $out=$out.$account."\t".$data["users"][$account]["sequence"]."\t".$data["users"][$account]["rank"]."\n";
  }
  return $out;
}

#####################################################################################################

?>
