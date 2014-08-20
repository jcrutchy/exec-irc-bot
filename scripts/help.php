<?php

# gpl2
# by crutchy
# 21-aug-2014

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

$trailing=trim($argv[1]);
$dest=trim($argv[2]);
$nick=trim($argv[3]);
$alias=strtolower(trim($argv[4]));

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$cmd=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));
unset($parts);

if ($cmd=="")
{
  privmsg("http://wiki.soylentnews.org/wiki/IRC:exec#Quick_start");
  return;
}
if ($cmd[0]=="~")
{
  $cmd=substr($cmd,1);
}
$title="IRC:exec aliases";
$section=$cmd;
if (login(True)==False)
{
  return;
}
$text=get_text($title,$section,True,True);
if ($text!==False)
{
  for ($i=0;$i<min(count($text),3);$i++)
  {
    bot_ignore_next();
    privmsg(" $cmd > ".$text[$i]);
  }
  privmsg("http://wiki.soylentnews.org/wiki/IRC:exec_aliases#$cmd");
}
else
{
  privmsg("help for \"$cmd\" alias not found");
}
logout(True);

#####################################################################################################

/*

==unlock==

Syntax:
* ~weather location

Examples: (section optional in wiki)
* x

Related commands: (section optional in wiki)
* x

Developers: (not shown in irc)
* [[User:Crutchy|crutchy]]

Sources: (not shown in irc)
* x

*/

#####################################################################################################

?>
