<?php

# gpl2
# by crutchy
# 23-april-2014

ini_set("display_errors","on");

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];

#echo "$cmd\n";
#echo "$trailing\n";

/*
ACC response:
0 - no such user online or nickname not registered
1 - user not recognized as nickname's owner
2 - user recognized as owner via access list only
3 - user recognized as owner via password identification
*/

switch ($cmd)
{
  case "PRIVMSG":
    $parts=explode(" ",$trailing);
    if (count($parts)==2)
    {
      switch ($parts[0])
      {
        case "~acc":
          $nick=$parts[1];
          if ($nick<>"")
          {
            echo "IRC_RAW :exec PRIVMSG NickServ :acc $nick\n";
          }
          break;
      }
    }
    break;
  case "NOTICE":
    $parts=explode(" ",$trailing);
    if (count($parts)==3)
    {
      switch ($parts[1])
      {
        case "ACC":
          echo "IRC_RAW :exec PRIVMSG #test :".$parts[0].": ".$parts[2]."\n";
          break;
      }
    }
    break;
}

?>
