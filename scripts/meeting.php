<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

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

$meeting_chairs=array("crutchy","chromas");

$meeting_data_changed=False;

$meeting_data=get_array_bucket("MEETING_DATA_".$dest);

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
    meeting_open();
    break;
  case "close":
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
  # verify quorum
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
  global $meeting_chairs;
  global $meeting_data_changed;
  global $meeting_data;
  if ($dest=="")
  {
    return;
  }
  $account=users_get_account($nick);
  if (in_array($account,$meeting_chairs)==False)
  {
    return;
  }
  $meeting_data=array();
  $meeting_data["channel"]=$dest;
  $meeting_data["chairs"]=array();
  $chair=array();
  $chair["nick"]=$nick;
  $chair["start"]=microtime(True);
  $meeting_data["chairs"][]=$chair;
  $meeting_data["messages"]=array();
  $meeting_data["events"]=array();
  $meeting_data["initial nicks"]=users_get_nicks($dest);
  if ($trailing=="")
  {
    $trailing="pants meeting";
  }
  $meeting_data["description"]=$trailing;
  privmsg(chr(3)."10*** $nick has hereby called this $trailing to order in channel $dest");
  $meeting_data_changed=True;
}

#####################################################################################################

function meeting_close()
{
  global $nick;
  global $dest;
  global $trailing;
  global $meeting_chairs;
  global $meeting_data_changed;
  global $meeting_data;
  if ($dest=="")
  {
    return;
  }
  $account=users_get_account($nick);
  if (in_array($account,$meeting_chairs)==False)
  {
    return;
  }
  if (isset($meeting_data["description"])==False)
  {
    return;
  }
  if (isset($meeting_data["chairs"][0]["start"])==False)
  {
    return;
  }
  if (isset($meeting_data["initial nicks"])==False)
  {
    return;
  }
  $final_nicks=users_get_nicks($dest);
  $title="Test page";
  $section=$meeting_data["description"]." - ".date("F j Y",$meeting_data["chairs"][0]["start"]);
  $text="<p>this is meeting minutes text blah blah doorsnoker</p>";
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

?>
