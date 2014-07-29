<?php

# gpl2
# by crutchy
# 28-july-2014

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

define("BOARD_MEETING","Board of Directors Meeting of SoylentNews PBC");

date_default_timezone_set("UTC");

$trailing=rtrim($argv[1]);
$dest=strtolower(trim($argv[2]));
$nick=trim($argv[3]);

$admins=array("crutchy");
$board_members=array("crutchy");

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$cmd=strtoupper($parts[0]);
array_shift($parts);
$trailing=implode(" ",$parts);

$meeting_list=get_array_bucket("<<MEETING_LIST>>");
if (is_array($meeting_list)==False)
{
  $meeting_list=array();
}
$meeting=get_array_bucket("<<MEETING_DATA $dest>>");
switch ($cmd)
{
  case "330": # :irc.sylnt.us 330 exec crutchy crutchy :is logged in as
    if ((count($parts)==3) and ($parts[0]==NICK_EXEC))
    {
      $nick=$parts[1];
      $account=$parts[2];
      $commands=get_array_bucket("<<MEETING_COMMAND $nick>>");
      if (count($commands)>0)
      {
        for ($i=0;$i<count($commands);$i++)
        {
          $dest=$commands[$i]["dest"];
          $trailing=$commands[$i]["trailing"];
          $cmd=$commands[$i]["cmd"];
          switch ($cmd)
          {
            case "OPEN":
              if (in_array($account,$admins)==True)
              {
                $meeting["channel"]=$dest;
                $meeting["chairs"]=array();
                $chair["nick"]=$nick;
                $chair["start"]=microtime(True);
                $meeting["chairs"][]=$chair;
                $meeting["finish"]="";
                $meeting["messages"]=array();
                $meeting["events"]=array();
                $meeting["initial nicks"]=array();
                $meeting["initial nicks complete"]=False;
                $meeting["final nicks"]=array();
                $meeting["final nicks complete"]=False;
                $meeting["quorum"]=False;
                if ($trailing=="")
                {
                  $trailing=BOARD_MEETING;
                }
                $meeting["description"]=$trailing;
                rawmsg("WHO $dest %ctnf,152");
                privmsg(chr(3)."10*** $nick has hereby called this $trailing to order");
                if ($trailing==BOARD_MEETING)
                {
                  for ($j=0;$j<count($voting_members);$j++)
                  {
                  }
                }
              }
              break;
            case "CLOSE":
              if (in_array($account,$admins)==True)
              {
                if (count($meeting)>0)
                {
                  privmsg(chr(3)."10*** $nick hereby declares this $trailing adjourned");
                  rawmsg("WHO $dest %ctnf,152");
                }
              }
              break;
          }
        }
      }
      unset_bucket("<<MEETING_COMMAND $nick>>");
    }
    break;
  case "318": # :irc.sylnt.us 318 exec crutchy :End of /WHOIS list.
    if ((count($parts)==2) and ($parts[0]==NICK_EXEC))
    {
      $nick=$parts[1];
      unset_bucket("<<MEETING_COMMAND $nick>>");
    }
    break;
  case "315": # :irc.sylnt.us 315 crutchy #Soylent :End of /WHO list.
    if ((count($parts)==2) and ($parts[0]==NICK_EXEC))
    {
      $dest=strtolower($parts[1]);
      $meeting=get_array_bucket("<<MEETING_DATA $dest>>");
      if (count($meeting)>0)
      {
        if ($meeting["initial nicks complete"]==False)
        {
          $meeting["initial nicks complete"]=True;
        }
        else
        {
          $meeting["final nicks complete"]=True;
          output_minutes($meeting);
        }
      }
    }
    break;
  case "354": # :irc.sylnt.us 354 exec 152 #Soylent mrcoolbp H@+
    if ((count($parts)==5) and ($parts[0]==NICK_EXEC))
    {
      if ($parts[1]=="152")
      {
        $dest=strtolower($parts[2]);
        $meeting=get_array_bucket("<<MEETING_DATA $dest>>");
        if (count($meeting)>0)
        {
          $nick=$parts[3];
          $mode=$parts[4];
          $data["nick"]=$nick;
          $data["mode"]=$mode;
          if ($meeting["initial nicks complete"]==False)
          {
            $meeting["initial nicks"][]=$data;
          }
          else
          {
            $meeting["final nicks"][]=$data;
          }
        }
      }
    }
    break;
  case "PRIVMSG":
    if (count($meeting)>0)
    {
      $msg["nick"]=$nick;
      $msg["timestamp"]=microtime(True);
      $msg["trailing"]=$trailing;
      $meeting["messages"][]=$msg;
    }
    break;
  case "CLOSE":
    if (count($meeting)>0)
    {
      set_admin_cmd($nick,$dest,$trailing,$cmd);
    }
    break;
  case "OPEN":
    term_echo("=== MEETING OPEN ===");
    set_admin_cmd($nick,$dest,$trailing,$cmd);
    break;
  case "QUIT":
    if (count($meeting)>0)
    {
      # verify quorum
    }
    break;
  case "PART":
    if (count($meeting)>0)
    {
      # verify quorum
    }
    break;
  case "KICK":
    if (count($meeting)>0)
    {
      # verify quorum
    }
    break;
  case "JOIN":
    break;
}
if (count($meeting)>0)
{
  set_array_bucket($meeting,"<<MEETING_DATA $dest>>");
  if (in_array("<<MEETING_DATA $dest>>",$meeting_list)==False)
  {
    $meeting_list[]="<<MEETING_DATA $dest>>";
  }
  set_array_bucket($meeting_list,"<<MEETING_LIST>>");
}

#####################################################################################################

function set_admin_cmd($nick,$dest,$trailing,$cmd)
{
  $commands=get_array_bucket("<<MEETING_COMMAND $nick>>");
  $new["dest"]=$dest;
  $new["trailing"]=$trailing;
  $new["cmd"]=$cmd;
  $commands[]=$new;
  set_array_bucket($commands,"<<MEETING_COMMAND $nick>>");
  rawmsg("WHOIS $nick");
}

#####################################################################################################

function output_minutes($meeting)
{
  var_dump($meeting);
}

#####################################################################################################

?>
