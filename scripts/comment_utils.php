<?php

# gpl2
# by crutchy
# 30-aug-2014

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

/*privmsg("hello world");
term_echo("message appears in terminal only");
pm(NICK_EXEC,"message");
err("show this message in terminal and die");
$data=get_bucket("index");
set_bucket("index",$data);
wget($host,$uri,80);*/

switch ($alias)
{
  case "~cid":
    $cid=get_bucket("<<SN_COMMENT_FEED_CID>>");
    if ($cid<>"")
    {
      privmsg("max SN cid from articles in atom feed: $cid");
    }
    return;
}

#####################################################################################################

?>
