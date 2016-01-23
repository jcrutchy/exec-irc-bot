<?php

#####################################################################################################
################################### ROCK / PAPER / SCISSORS GAME ####################################
#####################################################################################################

/*
exec:~rps|10|0|0|1||PRIVMSG|||php scripts/rps.php %%trailing%% %%dest%% %%nick%% %%alias%% %%params%% %%server%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$params=$argv[5];
$server=$argv[6];

$data=get_array_bucket("<<EXEC_RPS_DATA>>");

if ((valid_rps_sequence($trailing)==True) and ($trailing<>""))
{
  $account=users_get_account($nick);
  if ($account=="")
  {
    privmsg("you need to identify with nickserv to play");
    return;
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
  $data["users"][$account]["sequence"]=$data["users"][$account]["sequence"].$trailing;
  $data["rounds"]=max($data["rounds"],strlen($data["users"][$account]["sequence"]));
  set_array_bucket($data,"<<EXEC_RPS_DATA>>");
  $ranks=update_ranking($data);
  privmsg("rank for $account: ".$data["users"][$account]["rank"]." - http://ix.io/nAz");
  output_ixio_paste($ranks,False);
  return;
}

if ($trailing=="ranks")
{
  output_ixio_paste(get_ranking($data));
  return;
}

privmsg("syntax: ~rps [ranks|r|p|s]");
privmsg("rankings: http://ix.io/nAz");

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

function update_ranking(&$data)
{
  global $server;
  foreach ($data["users"] as $account => $user_data)
  {
    $data["users"][$account]["wins"]=0;
    $data["users"][$account]["losses"]=0;
    $data["users"][$account]["ties"]=0;
    for ($i=0;$i<strlen($data["users"][$account]["sequence"]);$i++)
    {
      foreach ($data["users"] as $sub_account => $sub_user_data)
      {
        if (isset($data["users"][$sub_account]["sequence"][$i])==True)
        {
          if ($sub_account==$account)
          {
            continue;
          }
          switch ($data["users"][$account]["sequence"][$i])
          {
            case "r":
              switch ($data["users"][$sub_account]["sequence"][$i])
              {
                case "r":
                  $data["users"][$account]["ties"]=$data["users"][$account]["ties"]+1;
                  break;
                case "p":
                  $data["users"][$account]["losses"]=$data["users"][$account]["losses"]+1;
                  break;
                case "s":
                  $data["users"][$account]["wins"]=$data["users"][$account]["wins"]+1;
                  break;
              }
              break;
            case "p":
              switch ($data["users"][$sub_account]["sequence"][$i])
              {
                case "r":
                  $data["users"][$account]["wins"]=$data["users"][$account]["wins"]+1;
                  break;
                case "p":
                  $data["users"][$account]["ties"]=$data["users"][$account]["ties"]+1;
                  break;
                case "s":
                  $data["users"][$account]["losses"]=$data["users"][$account]["losses"]+1;
                  break;
              }
              break;
            case "s":
              switch ($data["users"][$sub_account]["sequence"][$i])
              {
                case "r":
                  $data["users"][$account]["losses"]=$data["users"][$account]["losses"]+1;
                  break;
                case "p":
                  $data["users"][$account]["wins"]=$data["users"][$account]["wins"]+1;
                  break;
                case "s":
                  $data["users"][$account]["ties"]=$data["users"][$account]["ties"]+1;
                  break;
              }
              break;
          }
        }
      }
    }
  }
  $rankings=array();
  foreach ($data["users"] as $account => $user_data)
  {
    $data["users"][$account]["rank"]=0;
    $rankings[$account]=$data["users"][$account]["losses"]/$data["users"][$account]["wins"]*100*$data["rounds"]*$data["rounds"]/strlen($data["users"][$account]["sequence"]);
  }
  ksort($rankings);
  uasort($rankings,"ranking_sort_callback");
  $ranking_keys=array_keys($rankings);
  foreach ($data["users"] as $account => $user_data)
  {
    $data["users"][$account]["rank"]=array_search($account,$ranking_keys)+1;
  }
  $out="rankings for $server after ".$data["rounds"]." rounds:\n\n";
  $actlen=0;
  foreach ($data["users"] as $account => $user_data)
  {
    if (strlen($account)>$actlen)
    {
      $actlen=strlen($account);
    }
  }
  $head_account="account";
  $actlen=max($actlen,strlen($head_account));
  $out=$out.$head_account.str_repeat(" ",$actlen-strlen($head_account))."\tturns\twins\tloss\tties\t% wins\trank\thandicap\n";
  foreach ($rankings as $account => $rank)
  {
    $out=$out.$account.str_repeat(" ",$actlen-strlen($account))."\t".strlen($data["users"][$account]["sequence"])."\t".$data["users"][$account]["wins"]."\t".$data["users"][$account]["losses"]."\t".$data["users"][$account]["ties"]."\t".sprintf("%.0f",$data["users"][$account]["wins"]/$data["users"][$account]["losses"]*100)."\t".$data["users"][$account]["rank"]."\t".str_pad(sprintf("%.1f",$rankings[$account]/$data["rounds"]),strlen("handicap")," ",STR_PAD_LEFT)."\n";
  }
  $out=$out."\nhandicap = losses/wins/turns*rounds*100";
  return $out;
}

#####################################################################################################

function ranking_sort_callback($a,$b)
{
  return ($a-$b);
}

#####################################################################################################

?>
