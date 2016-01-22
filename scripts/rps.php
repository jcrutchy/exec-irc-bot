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
  if (strlen($trailing)>($data["rounds"]+1))
  {
    $trailing=substr($trailing,0,$data["rounds"]+1);
  }
  if (isset($data["users"])==False)
  {
    $data["users"]=array();
  }
  if (isset($data["users"][$account])==False)
  {
    $data["users"][$account]=array();
  }
  $ts=microtime(True);
  if (isset($data["users"][$account]["timestamp"])==True)
  {
    if (($ts-$data["users"][$account]["timestamp"])<5.0)
    {
      privmsg();
    }
  }
  $data["users"][$account]["timestamp"]=$ts;
  if (isset($data["users"][$account]["sequence"])==False)
  {
    $data["users"][$account]["sequence"]="";
  }
  $data["users"][$account]["sequence"]=$data["users"][$account]["sequence"].$trailing;
  $data["rounds"]=max($data["rounds"],strlen($trailing));
  set_array_bucket($data,"<<EXEC_RPS_DATA>>");
  output_ixio_paste(get_ranking($data));
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
    switch ($trailing)
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

function get_ranking($data)
{
  $out="";
  foreach ($data["users"] as $account => $user_data)
  {
    $out=$out.$account."\t".$user_data["sequence"]."\n";
  }
}

#####################################################################################################

?>
