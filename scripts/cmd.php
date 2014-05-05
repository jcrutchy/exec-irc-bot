<?php

# gpl2
# by crutchy
# 27-april-2014

#####################################################################################################

define("CHAN_CIV","#civ");
define("NICK","exec");

ini_set("display_errors","on");

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];

switch ($cmd)
{
  case "330": # is logged in as
    $parts=explode(" ",$params);
    if ((count($parts)==3) and ($parts[0]==NICK))
    {
      $nick=$parts[1];
      $account=$parts[2];
      if ($nick<>NICK)
      {
        echo ":".NICK." NOTICE ".CHAN_CIV." :civ login $nick $account\n";
        echo ":$nick NOTICE ".CHAN_CIV." :~lock civ\n";
        sleep(1);
        echo ":$nick NOTICE ".CHAN_CIV." :flag public_status\n";
        sleep(1);
        echo ":$nick NOTICE ".CHAN_CIV." :status\n";
      }
    }
    break;
  case "353": # channel names list
    sleep(3);
    $parts=explode(" = ",$params);
    if (count($parts)==2)
    {
      if (($parts[0]==NICK) and ($parts[1]==CHAN_CIV))
      {
        $names=explode(" ",$trailing);
        for ($i=0;$i<count($names);$i++)
        {
          $name=$names[$i];
          if ((substr($name,0,1)=="+") or (substr($name,0,1)=="@"))
          {
            $name=substr($name,1);
          }
          if ($name==NICK)
          {
            continue;
          }
          echo "IRC_RAW WHOIS $name\n";
          sleep(1);
        }
      }
    }
    break;
  case "JOIN":
    if ($dest==CHAN_CIV)
    {
      if ($nick==NICK)
      {
        echo ":crutchy NOTICE #civ :civ-map generate\n";
      }
      echo "IRC_RAW WHOIS $nick\n";
    }
    break;
  case "PART":
    if ($dest==CHAN_CIV)
    {
      echo ":".NICK." NOTICE ".CHAN_CIV." :civ logout $nick\n";
    }
    break;
  case "NICK":
    echo ":".NICK." NOTICE ".CHAN_CIV." :civ rename $nick $trailing\n";
    break;
  case "PRIVMSG":
    break;
  case "NOTICE":
    break;
  case "MODE":
    break;
}

#####################################################################################################

function term_echo($msg)
{
  echo "\033[35m$msg\033[0m\n";
}

#####################################################################################################

?>
