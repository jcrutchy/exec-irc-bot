<?php

#####################################################################################################

/*
#exec:~meeting|60|0|0|1|||||php scripts/meeting.php %%nick%% %%trailing%% %%dest%% %%start%% %%alias%% %%cmd%%
#init:~meeting register-events
*/

#####################################################################################################

# http://wiki.soylentnews.org/wiki/Board_Meetings_Rules_of_Order

ini_set("display_errors","on");

require_once("lib.php");
require_once("wiki_lib.php");

define("BOARD_MEETING","SoylentNews PBC Board of Directors Meeting");

$options_yes=array("yea","aye");
$options_no=array("nay");

date_default_timezone_set("UTC");

$nick=strtolower(trim($argv[1]));
$trailing=trim($argv[2]);
$dest=strtolower(trim($argv[3]));
$start=trim($argv[4]);
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));

if ($trailing=="")
{
  privmsg("SN meeting script");
  return;
}

$meeting_chair_accounts=array("crutchy","chromas");
$board_member_accounts=array("crutchy","chromas");
$board_member_quorum=2;

$meeting_data_changed=False;

$meeting_data=array();
if ($dest<>"")
{
  $meeting_data=get_array_bucket("MEETING_DATA_".$dest);
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

term_echo("meeting: $trailing");

switch ($action)
{
  case "register-events":
    register_all_events("~meeting",True);
    return;
  case "event-join":
    # trailing = <nick> <channel>
    meeting_join();
    break;
  case "event-kick":
    # trailing = <channel> <nick>
    meeting_kick();
    break;
  case "event-nick":
    # trailing = <old-nick> <new-nick>
    meeting_nick();
    break;
  case "event-part":
    # trailing = <nick> <channel>
    meeting_part();
    break;
  case "event-quit":
    # trailing = <nick>
    meeting_quit();
    break;
  case "event-privmsg":
    # trailing = <nick> <channel> <trailing>
    meeting_privmsg();
    break;
  case "open":
  case "opened":
  case "call":
  case "called":
  case "start":
  case "started":
  case "commence":
  case "commenced":
    meeting_open();
    break;
  case "close":
  case "closed":
  case "adjourn":
  case "adjourned":
  case "end":
  case "ended":
  case "finish":
  case "finished":
    meeting_close();
    return;
  case "chair":
    meeting_chair();
    break;
  case "vote":
  case "motion":
    meeting_vote();
    break;
  case "assign":
  case "assignment":
  case "todo":
  case "to-do":
    meeting_assign();
    break;
}

if (($meeting_data_changed==True) and ($dest<>""))
{
  set_array_bucket($meeting_data,"MEETING_DATA_".$dest);
}

#####################################################################################################

function meeting_join()
{
  global $parts;
  global $meeting_data;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <nick> <channel>
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  term_echo("meeting_join: nick=$nick, channel=$channel");
  $meeting_data=get_array_bucket("MEETING_DATA_".$channel);
  if (isset($meeting_data["description"])==False)
  {
    return;
  }
  $data=array();
  $data["nick"]=$nick;
  $data["timestamp"]=microtime(True);
  $data["cmd"]="JOIN";
  $meeting_data["events"][]=$data;
}

#####################################################################################################

function meeting_kick()
{
  global $parts;
  global $meeting_data;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <channel> <nick>
  $nick=strtolower($parts[1]);
  $channel=strtolower($parts[0]);
  term_echo("meeting_kick: nick=$nick, channel=$channel");
  $meeting_data=get_array_bucket("MEETING_DATA_".$channel);
  if (isset($meeting_data["description"])==False)
  {
    return;
  }
  verify_quorum();
  $data=array();
  $data["nick"]=$nick;
  $data["timestamp"]=microtime(True);
  $data["cmd"]="KICK";
  $meeting_data["events"][]=$data;
}

#####################################################################################################

function meeting_nick()
{
  global $parts;
  global $meeting_data;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <old-nick> <new-nick>
  $old_nick=strtolower($parts[0]);
  $new_nick=strtolower($parts[1]);
  term_echo("meeting_nick: old_nick=$old_nick, new_nick=$new_nick");
  $channels=meeting_channel_list();
  for ($i=0;$i<count($channels);$i++)
  {
    $channel=$channels[$i];
    $meeting_data=get_array_bucket("MEETING_DATA_".$channel);
    if (isset($meeting_data["description"])==False)
    {
      term_echo("meeting_nick: meeting description not found in meeting data for channel $channel");
      continue;
    }
    verify_quorum();
    $data=array();
    $data["nick"]=$new_nick;
    $data["timestamp"]=microtime(True);
    $data["cmd"]="NICK";
    $data["old"]=$old_nick;
    $meeting_data["events"][]=$data;
  }
}

#####################################################################################################

function meeting_part()
{
  global $parts;
  global $meeting_data;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <nick> <channel>
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  term_echo("meeting_part: nick=$nick, channel=$channel");
  $meeting_data=get_array_bucket("MEETING_DATA_".$channel);
  if (isset($meeting_data["description"])==False)
  {
    return;
  }
  verify_quorum();
  $data=array();
  $data["nick"]=$nick;
  $data["timestamp"]=microtime(True);
  $data["cmd"]="PART";
  $meeting_data["events"][]=$data;
}

#####################################################################################################

function meeting_quit()
{
  global $trailing;
  global $meeting_data;
  $nick=strtolower($trailing);
  term_echo("meeting_quit: nick=$nick");
  $channels=meeting_channel_list();
  for ($i=0;$i<count($channels);$i++)
  {
    $channel=$channels[$i];
    $meeting_data=get_array_bucket("MEETING_DATA_".$channel);
    if (isset($meeting_data["description"])==False)
    {
      continue;
    }
    verify_quorum();
    $data=array();
    $data["nick"]=$nick;
    $data["timestamp"]=microtime(True);
    $data["cmd"]="QUIT";
    $meeting_data["events"][]=$data;
  }
}

#####################################################################################################

function meeting_privmsg()
{
  global $parts;
  global $meeting_data;
  global $board_member_accounts;
  global $options_yes;
  global $options_no;
  if (count($parts)<3)
  {
    term_echo("meeting: invalid number of trailing parts");
    return;
  }
  # trailing = <nick> <channel> <trailing>
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  array_shift($parts);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  term_echo("meeting_privmsg: nick=$nick, channel=$channel, trailing=$trailing");
  $meeting_data=get_array_bucket("MEETING_DATA_".$channel);
  if (isset($meeting_data["description"])==False)
  {
    term_echo("meeting: no meeting open");
    return;
  }
  $data=array();
  $data["nick"]=$nick;
  $data["timestamp"]=microtime(True);
  $data["trailing"]=$trailing;
  $meeting_data["messages"][]=$data;
  $test=strtolower($trailing);
  if ((in_array($test,$options_yes)==True) or (in_array($test,$options_no)==True))
  {
    $account=users_get_account($nick);
    if (in_array($account,$board_member_accounts)==False)
    {
      meeting_event_msg($channel,"$nick is not an authenticated board member. vote not counted");
    }
    $n=count($meeting_data["motions"]);
    if ($n==0)
    {
      meeting_event_msg($channel,"no motions registered. vote not counted");
    }
    else
    {
      $motion=&$meeting_data["motions"][$n-1];
      $vote_yes=array_search($account,$motion["votes"]["yes"]);
      $vote_no=array_search($account,$motion["votes"]["no"]);
      if ($vote_yes!==False)
      {
        unset($motion["votes"]["yes"][$vote_yes]);
        $motion["votes"]["yes"]=array_values($motion["votes"]["yes"]);
      }
      if ($vote_no!==False)
      {
        unset($motion["votes"]["no"][$vote_no]);
        $motion["votes"]["no"]=array_values($motion["votes"]["no"]);
      }
      if (($vote_yes!==False) or ($vote_no!==False))
      {
        meeting_event_msg($channel,"vote by $nick [$account] for current motion already registered. previous vote deleted");
      }
      if (in_array($test,$options_yes)==True)
      {
        $motion["votes"]["yes"][]=$account;
        meeting_event_msg($channel,"'aye' vote registered for $nick [$account] for current motion");
      }
      else
      {
        $motion["votes"]["no"][]=$account;
        meeting_event_msg($channel,"'nay' vote registered for $nick [$account] for current motion");
      }
    }
  }
}

#####################################################################################################

function meeting_open()
{
  global $nick;
  global $dest;
  global $trailing;
  global $meeting_chair_accounts;
  global $meeting_data_changed;
  global $meeting_data;
  if ($dest=="")
  {
    return;
  }
  $account=users_get_account($nick);
  if (in_array($account,$meeting_chair_accounts)==False)
  {
    return;
  }
  if ($trailing=="")
  {
    $trailing=BOARD_MEETING;
  }
  $members=verify_quorum();
  if ($trailing==BOARD_MEETING)
  {
    if ($members===False)
    {
      privmsg("unable to open board meeting due to lack of required quorum");
      privmsg("attending board members please identify with nickserv for verification prior to the chair opening the meeting");
      return;
    }
  }
  if (strpos(strtolower($trailing),"meeting")===False)
  {
    $trailing=trim($trailing)." meeting";
  }
  $start_time=microtime(True);
  $meeting_data=array();
  $meeting_data["quorum"]=array();
  $meeting_data["quorum"][]=$members;
  $meeting_data["channel"]=$dest;
  $meeting_data["chairs"]=array();
  $chair=array();
  $chair["nick"]=$nick;
  $chair["start"]=$start_time;
  $meeting_data["chairs"][]=$chair;
  $meeting_data["messages"]=array();
  $meeting_data["events"]=array();
  $meeting_data["motions"]=array();
  $meeting_data["tasks"]=array();
  $meeting_data["nicks"]=users_get_nicks($dest);
  $meeting_data["description"]=$trailing;
  $meeting_data_changed=True;
  meeting_msg("================== $trailing ==================");
  #meeting_msg("$nick hereby calls this meeting to order");
  #meeting_msg("meeting has commenced @ ".date("H:i (T)",$start_time)." on ".date("l, j F Y",$start_time));
  #meeting_msg("meeting is currently chaired by $nick");
  if ($trailing==BOARD_MEETING)
  {
    #meeting_msg("note: this is an official board meeting; only board members may vote");
  }
}

#####################################################################################################

function meeting_close()
{
  global $nick;
  global $dest;
  global $trailing;
  global $meeting_chair_accounts;
  global $meeting_data_changed;
  global $meeting_data;
  if ($dest=="")
  {
    return;
  }
  $account=users_get_account($nick);
  if (in_array($account,$meeting_chair_accounts)==False)
  {
    return;
  }
  if (isset($meeting_data["description"])==False)
  {
    meeting_msg("meeting not currently registered in this channel");
    return;
  }
  $finish_time=microtime(True);
  #meeting_msg("$nick hereby adjourns the meeting at ".date("H:i (T)",$finish_time)." on ".date("l, j F Y",$finish_time));
  meeting_msg("================== // ==================");
  #privmsg("preparing minutes and posting to wiki...");
  $title="Test page";
  $section=$meeting_data["description"]." - ".date("F j Y",$meeting_data["chairs"][0]["start"]);
  $start_time=$meeting_data["chairs"][0]["start"];
  $text="<p>start time: ".date("H:i (T)",$start_time)." on ".date("l, j F Y",$start_time)."<br />";
  $text=$text."finish time: ".date("H:i (T)",$finish_time)." on ".date("l, j F Y",$finish_time)."</p>";
  $text=$text."<p>location: irc.sylent.us, channel $dest</p>";
  $text=$text."<p>opening chair: ".$meeting_data["chairs"][0]["nick"]."</p>";
  if (count($meeting_data["chairs"])>1)
  {
    $text=$text."<p>other chair(s):<br />";
    for ($i=1;$i<count($meeting_data["chairs"]);$i++)
    {
      if ($i>1)
      {
        $text=$text."<br />";
      }
      $text=$text."* ".$meeting_data["chairs"][$i]["nick"];
    }
    $text=$text."</p>";
  }
  $text=$text."<p>attendees (voiced/voter/joined/parted/quit/kicked):</p>";
  $text=$text."<p>".implode(", ",$meeting_data["nicks"])."</p>";
  if ($meeting_data["description"]==BOARD_MEETING)
  {
    $agenda=get_text("Issues to Be Raised at the Next Board Meeting","Issues/Agenda",True,True);
    if ($agenda!==False)
    {
      if (count($agenda)>0)
      {
        $text=$text."<p>agenda items:<br />";
        for ($i=0;$i<count($agenda);$i++)
        {
          if ($i>1)
          {
            $text=$text."<br />";
          }
          $text=$text."* ".$agenda[$i];
        }
      }
    }
  }
  $text=$text."<p>table of motions (ayes/nays/carried):</p>";
  $text=$text."<p>table of assignments:</p>";
  $text=$text."<p>formatted irc script:</p>";
  /*if (login(True)==False)
  {
    privmsg("error logging into wiki");
    return;
  }
  if (edit($title,$section,$text,True)==False)
  {
    privmsg("error updating wiki");
  }
  else
  {
    privmsg("successfully updated wiki - http://wiki.soylentnews.org/wiki/Test_page");
  }
  logout(True);*/
  $meeting_data=array();
  unset_bucket("MEETING_DATA_".$dest);
}

#####################################################################################################

function meeting_chair()
{

}

#####################################################################################################

function meeting_vote()
{

}

#####################################################################################################

function meeting_assign()
{

}

#####################################################################################################

function verify_quorum()
{
  global $dest;
  global $board_member_accounts;
  global $board_member_quorum;
  global $meeting_data;
  if (isset($meeting_data["quorum"])==False)
  {
    privmsg("verifying quorum...");
  }
  else
  {
    meeting_msg("verifying quorum...");
  }
  $members=array();
  # first try nick same as account
  for ($i=0;$i<count($board_member_accounts);$i++)
  {
    $nick=$board_member_accounts[$i];
    $user=users_get_data($nick);
    $account="";
    if ((isset($user["account"])==True) and (isset($user["account_updated"])==True))
    {
      $delta=microtime(True)-$user["account_updated"];
      if ($delta<=(30*60)) # not older than 30 minutes
      {
        $account=$user["account"];
        #privmsg("1: [get_data] nick=$nick, account=$account");
      }
    }
    if ($account=="")
    {
      $account=users_get_account($nick);
      usleep(0.5e6);
      #privmsg("1: [whois] nick=$nick, account=$account");
    }
    if ((in_array($account,$board_member_accounts)==True) and (in_array($account,$members)==False))
    {
      $members[]=$account;
      #privmsg("[".count($members)."] nick=$nick, account=$account");
      if (count($members)>=$board_member_quorum)
      {
        return $members;
      }
    }
  }
  # cycle through all nicks in channel (except for nicks that are the same as board member accounts)
  $nicks=users_get_nicks($dest);
  for ($i=0;$i<count($nicks);$i++)
  {
    $nick=$nicks[$i];
    if (in_array($nick,$board_member_accounts)==True)
    {
      continue;
    }
    $user=users_get_data($nick);
    $account="";
    if ((isset($user["account"])==True) and (isset($user["account_updated"])==True))
    {
      $delta=microtime(True)-$user["account_updated"];
      if ($delta<=(30*60)) # not older than 30 minutes
      {
        $account=$user["account"];
        #privmsg("2: [get_data] nick=$nick, account=$account");
      }
    }
    if ($account=="")
    {
      $account=users_get_account($nick);
      usleep(0.5e6);
      #privmsg("2: [whois] nick=$nick, account=$account");
    }
    if ((in_array($account,$board_member_accounts)==True) and (in_array($account,$members)==False))
    {
      $members[]=$account;
      #privmsg("[".count($members)."] nick=$nick, account=$account");
      if (count($members)>=$board_member_quorum)
      {
        return $members;
      }
    }
  }
  return False;
}

#####################################################################################################

function meeting_msg($msg)
{
  privmsg(chr(3)."10".$msg);
}

#####################################################################################################

function meeting_event_msg($channel,$msg)
{
  pm($channel,chr(3)."10".$msg);
}

#####################################################################################################

function meeting_channel_list()
{
  $buckets=bucket_list();
  $buckets=explode(" ",$buckets);
  $channels=array();
  $prefix="MEETING_DATA_";
  for ($i=0;$i<count($buckets);$i++)
  {
    if (substr($buckets[$i],0,strlen($prefix))==$prefix)
    {
      $channels[]=substr($buckets[$i],strlen($prefix));
    }
  }
  return $channels;
}

#####################################################################################################

?>
