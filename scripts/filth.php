<?php

#####################################################################################################

/*
exec:~filth|30|7200|0|1|||||php scripts/filth.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

#return;

require_once("lib.php");
require_once("lib_mysql.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];

if ($trailing=="")
{
  $records=fetch_query("SELECT * FROM exec_irc_bot.irc_log WHERE ((destination='#') AND (nick!='irc.sylnt.us') AND (server='irc.sylnt.us') AND (nick!='exec') AND (cmd='PRIVMSG') AND (`trailing` not like '%--%') AND (`trailing` not like '%++%') AND (`trailing` not like '%karma%') AND (`trailing` not like '^%') AND (`trailing` not like '=%') AND (`trailing` not like '!%') AND (`trailing` not like '$%') AND (`trailing` not like '~%') AND (`trailing` not like 'ACTION%')) ORDER BY id");
  $i=count($records);
  do
  {
    $i--;
    $last=trim($records[$i]["trailing"]);
    $last_parts=explode(" ",$last);
  }
  while (count($last_parts)<4);
  $k=mt_rand(1,count($last_parts)-1);
  $i=0;
  $replacements=array();
  while ($i<=$k)
  {
    $n=mt_rand(0,count($records)-2);
    $msg=trim(filter($records[$n]["trailing"],VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC." "));
    $msg_parts=explode(" ",$msg);
    $msg_n=mt_rand(0,count($msg_parts)-1);
    $replace=$msg_parts[$msg_n];
    $L=strlen($replace);
    if (($L<4) or ($L>20))
    {
      continue;
    }
    if (in_array($replace,$replacements)==True)
    {
      continue;
    }
    $i++;
    $last_n=mt_rand(0,count($last_parts)-1);
    $last_parts[$last_n]=$replace;
    $replacements[]=$replace;
  }
  for ($i=0;$i<count($last_parts);$i++)
  {
    $last_parts[$i]=strtolower($last_parts[$i]);
  }
  $last="ciri: ".implode(" ",$last_parts);
  if ($dest=="")
  {
    pm("#",$last);
  }
  elseif ($dest=="#")
  {
    privmsg($last);
  }
}
else
{
  # google search using $trailing (maybe later)
}

#####################################################################################################

?>
