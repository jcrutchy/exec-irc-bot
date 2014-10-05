<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

define("BOARD_MEETING","SoylentNews PBC Board of Directors Meeting");

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
}

if (($meeting_data_changed==True) and ($dest<>""))
{
  set_array_bucket($meeting_data,"MEETING_DATA_".$dest);
}

#####################################################################################################

function meeting_join()
{

}

#####################################################################################################

function meeting_kick()
{
  # verify quorum
}

#####################################################################################################

function meeting_nick()
{

}

#####################################################################################################

function meeting_part()
{
  # verify quorum
}

#####################################################################################################

function meeting_quit()
{
  # verify quorum
}

#####################################################################################################

function meeting_privmsg()
{
  global $parts;
  if (count($parts)<3)
  {
    return;
  }
  # trailing = <nick> <channel> <trailing>
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  array_shift($parts);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  term_echo("meeting_privmsg: nick=$nick, channel=$channel, trailing=$trailing");
  switch (strtolower($trailing))
  {
    case "aye":
    case "yes":

      break;
    case "nay":
    case "no":

      break;
  }
/*
$msg["nick"]=$nick;
$msg["timestamp"]=microtime(True);
$msg["trailing"]=$trailing;
$meeting["messages"][]=$msg;
*/
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
  if ($trailing==BOARD_MEETING)
  {
    if (initialize_quorum()==False)
    {
      meeting_msg("unable to open board meeting due to lack of required quorum");
      meeting_msg("attending board members please identify with nickserv for verification prior to the chair opening the meeting");
      return;
    }
  }
  if (strpos(strtolower($trailing),"meeting")===False)
  {
    $trailing=trim($trailing)." meeting";
  }
  $start_time=microtime(True);
  $meeting_data=array();
  $meeting_data["channel"]=$dest;
  $meeting_data["chairs"]=array();
  $chair=array();
  $chair["nick"]=$nick;
  $chair["start"]=$start_time;
  $meeting_data["chairs"][]=$chair;
  $meeting_data["messages"]=array();
  $meeting_data["events"]=array();
  $meeting_data["initial nicks"]=users_get_nicks($dest);
  $meeting_data["description"]=$trailing;
  $meeting_data_changed=True;
  meeting_msg("================== $trailing ==================");
  meeting_msg("$nick has hereby called this $trailing to order");
  meeting_msg("meeting commenced @ ".date("H:i (T)",$start_time)." on ".date("l, j F Y",$start_time));
  meeting_msg("meeting is currently chaired by $nick");
  if ($trailing==BOARD_MEETING)
  {
    meeting_msg("note: this is an official board meeting; only board members may vote");
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
  meeting_msg("meeting adjourned @ ".date("H:i (T)",$finish_time)." on ".date("l, j F Y",$finish_time));
  meeting_msg("================== // ==================");
  privmsg("preparing minutes and posting to wiki. please wait...");
  $final_nicks=users_get_nicks($dest);
  $title="Test page";
  $section=$meeting_data["description"]." - ".date("F j Y",$meeting_data["chairs"][0]["start"]);

/*
agenda items
start time
finish time
chair(s)
attendees
  list @ beginning
  list @ end
  op
  voice
  speakers
  authorized voters
  admins
  joins
  parts/quits/kicks
table of motions
  vote counts
  carry status
  oppositions
  raised
  seconded
formatted irc script
*/

  $wiki_new_line="<br />";

  $text="<p>this is meeting minutes text blah blah doorsnoker</p>";

  if ($meeting_data["description"]==BOARD_MEETING)
  {
    $agenda=get_text("Issues to Be Raised at the Next Board Meeting","Issues/Agenda",True,True);
    if ($agenda!==False)
    {
      if (count($agenda)>0)
      {
        $text=$text."===Agenda items===$wiki_new_line";
        for ($i=0;$i<count($agenda);$i++)
        {

        }
      }
    }
  }


  $text=$text."<p>Location: irc.sylent.us, channel $dest</p>";
  $text=$text."<p>Chairs:";
  for ($i=0;$i<count($meeting_data["chairs"]);$i++)
  {
  
  }
  $text=$text."</p>";
  if (login(True)==False)
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
  logout(True);
  $meeting_data=array();
  unset_bucket("MEETING_DATA_".$dest);
}

#####################################################################################################

function meeting_chair()
{

}

#####################################################################################################

function initialize_quorum()
{
  global $dest;
  global $board_member_accounts;
  global $board_member_quorum;
  privmsg("verifying quorum. please wait...");
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
        return True;
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
        return True;
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

?>
