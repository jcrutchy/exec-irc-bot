<?php

#####################################################################################################

/*
exec:~filth|30|2700|0|1|||||php scripts/filth.php %%trailing%% %%dest%% %%nick%%
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
  $records=fetch_query("SELECT * FROM exec_irc_bot.irc_log WHERE ((destination='#') AND (nick!='irc.sylnt.us') AND (server='irc.sylnt.us') AND (nick!='exec') AND (cmd='PRIVMSG') AND (`trailing` not like '%--%') AND (`trailing` not like '%++%') AND (`trailing` not like '%karma%') AND (`trailing` not like '=%') AND (`trailing` not like '!%') AND (`trailing` not like '$%') AND (`trailing` not like '~%') AND (`trailing` not like 'ACTION%')) ORDER BY id");
  $last=trim($records[count($records)-1]["trailing"]);
  $last_parts=explode(" ",$last);
  $k=mt_rand(1,min(10,count($last_parts)/2));
  $i=0;
  while ($i<=$k)
  {
    $n=mt_rand(0,count($records)-2);
    $msg=trim($records[$n]["trailing"]);
    $msg_parts=explode(" ",$msg);
    $msg_n=mt_rand(0,count($msg_parts)-1);
    $replace=$msg_parts[$msg_n];
    $L=strlen($replace);
    if (($L<3) or ($L>10))
    {
      continue;
    }
    $i++;
    $last_n=mt_rand(0,count($last_parts)-1);
    $last_parts[$last_n]=$replace;
  }
  $last=implode(" ",$last_parts);
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
