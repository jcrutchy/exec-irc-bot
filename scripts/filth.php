<?php

#####################################################################################################

/*
exec:~filth|30|300|0|1|||||php scripts/filth.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");
require_once("lib_mysql.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];

if ($trailing=="")
{
  # poke ciri with something random
  $records=fetch_query("SELECT * FROM exec_irc_bot.irc_log WHERE ((destination='#') AND (server='irc.sylnt.us') AND (nick!='exec') AND (cmd='PRIVMSG') AND (`trailing` not like '%--%') AND (`trailing` not like '%++%') AND (`trailing` not like '%karma%') AND (`trailing` not like '=%') AND (`trailing` not like '!%') AND (`trailing` not like '$%') AND (`trailing` not like '~%') AND (`trailing` not like 'ACTION%'))");
  $m=mt_rand(0,count($records));
  $n=mt_rand(0,count($records));
  $msg=$records[$m]["trailing"]." ".$records[$n]["trailing"];
  $parts=explode(" ",$msg);
  shuffle($parts);
  $parts2=array();
  for ($i=0;$i<count($parts);$i++)
  {
    if (strlen($parts[$i])>1)
    {
      $parts2[]=$parts[$i];
    }
  }
  if (count($parts2)>0)
  {
    $msg="ciri: ".implode(" ",$parts2);
    if ($dest=="")
    {
      pm("#",$msg);
    }
    elseif ($dest=="#")
    {
      privmsg($msg);
    }
  }
}
else
{
  # google search using $trailing
  
}

#####################################################################################################

?>
