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
  $records=fetch_query("SELECT * FROM exec_irc_bot.irc_log WHERE ((destination='#') AND (server='irc.sylnt.us') AND (nick!='exec') AND (cmd='PRIVMSG') AND (`trailing` not like '~%') AND (`trailing` not like 'ACTION%'))");
  $m=mt_rand(0,count($records));
  $n=mt_rand(0,count($records));
  $msg="ciri: ".$records[$m]["trailing"]." ".$records[$n]["trailing"];
  if ($dest=="")
  {
    pm("#",$msg);
  }
  elseif ($dest=="#")
  {
    privmsg($msg);
  }
}
else
{
  # google search using $trailing
  # wget($host,$uri,$port=80,$agent=ICEWEASEL_UA,$extra_headers="",$timeout=20,$breakcode="",$chunksize=1024)
}

#####################################################################################################

?>
