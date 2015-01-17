<?php

#####################################################################################################

/*
exec:~api|20|0|0|1|*|PRIVMSG|#Soylent,#,#journals||php scripts/sn_api.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$op=$parts[0];
array_shift($parts);
$trailing=trim(implode(" ",$parts));

$host="dev.soylentnews.org";
$port=80;

switch ($op)
{
  case "uid":
    $uri="/api.pl?m=user&op=get_uid&nick=".urlencode($trailing);
    $response=wget($host,$uri,$port);
    $content=strip_headers($response);
    $data=json_decode($content,True);
    if (isset($data["uid"])==True)
    {
      privmsg("  SN uid for user \"$trailing\" is ".$data["uid"]);
    }
    else
    {
      privmsg("  error: unable to retrive SN uid for user \"$trailing\"");
    }
    break;
  case "name":
    $uri="/api.pl?m=user&op=get_nick&uid=$trailing";
    $response=wget($host,$uri,$port);
    $content=strip_headers($response);
    $data=json_decode($content,True);
    if (isset($data["nick"])==True)
    {
      privmsg("  SN username for uid $trailing is \"".$data["nick"]."\"");
    }
    else
    {
      privmsg("  error: unable to retrive SN username for uid $trailing");
    }
    break;
  case "karma":
    $uname="";
    if (exec_is_integer($trailing)==False)
    {
      $uri="/api.pl?m=user&op=get_uid&nick=".urlencode($trailing);
      $response=wget($host,$uri,$port);
      $content=strip_headers($response);
      $data=json_decode($content,True);
      if (isset($data["uid"])==True)
      {
        $uname=$trailing;
        $trailing=$data["uid"];
      }
      else
      {
        privmsg("  error: invalid uid");
        break;
      }
    }
    else
    {
      $uri="/api.pl?m=user&op=get_nick&uid=$trailing";
      $response=wget($host,$uri,$port);
      $content=strip_headers($response);
      $data=json_decode($content,True);
      if (isset($data["nick"])==True)
      {
        $uname=$data["nick"];
      }
      else
      {
        privmsg("  error: invalid uid");
        break;
      }
    }
    $uri="/api.pl?m=user&op=get_user&uid=$trailing";
    $response=wget($host,$uri,$port);
    $content=strip_headers($response);
    $data=json_decode($content,True);
    if ((isset($data["karma"])==True) and ($uname<>""))
    {
      privmsg("  SN karma for \"$uname\" (uid $trailing) is ".$data["karma"]);
    }
    else
    {
      privmsg("  error: unable to retrive SN karma for uid $trailing");
    }
    break;
}

#####################################################################################################

?>
