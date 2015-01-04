<?php

#####################################################################################################

/*
exec:~feeds|500|0|0|1|crutchy||||php scripts/feeds.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~feeds-internal|500|1800|0|1||INTERNAL|||php scripts/feeds.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~feeds-sources|5|0|0|1|||||php scripts/feeds.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
require_once("feeds_lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$feed_chan="#rss-bot";

define("FEED_FILE","scripts/feeds.txt");
define("PAST_FEED_FILE","/var/www/irciv.us.to/exec_logs/feeds.txt");

$feed_list=load_feeds_from_file(FEED_FILE);
if ($feed_list===False)
{
  term_echo("error loading feeds file");
  return;
}
if (count($feed_list)==0)
{
  term_echo("no valid sources in feeds file");
  return;
}

if ($alias=="~feeds-sources")
{
  $msg="";
  for ($i=0;$i<count($feed_list);$i++)
  {
    if ($msg<>"")
    {
      $msg=$msg." ";
    }
    $msg=$msg."[".$feed_list[$i]["name"]."]";
  }
  if ($msg<>"")
  {
    privmsg(chr(2).$msg.chr(2));
  }
  return;
}

$items=get_new_items($feed_list);
#var_dump($items);
$c=min(3,count($items));
for ($i=0;$i<$c;$i++)
{
  $item=$items[$i];
  $msg=chr(2)."[".$item["feed_name"]."]".chr(2)." - ".chr(3)."3".$item["title"].chr(3)." - ".$item["url"];
  echo "/IRC :".NICK_EXEC." PRIVMSG ".$feed_chan." :$msg\n";
}

#####################################################################################################

?>
