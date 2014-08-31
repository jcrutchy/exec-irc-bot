<?php

# gpl2
# by crutchy
# 31-aug-2014

#####################################################################################################

require_once("lib.php");

define("COMMENTS_CID_FILE","../data/comments_cid.txt");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

switch ($alias)
{
  case "~cid":
    $cid=file_get_contents(COMMENTS_CID_FILE);
    if ($cid<>"")
    {
      privmsg("max SN cid from articles in atom feed: $cid");
    }
    return;
}

#####################################################################################################

?>
