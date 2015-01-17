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
    $uid=get_uid($trailing);
    if ($uid!==False)
    {
      privmsg("  SN uid for user \"$trailing\" is $uid");
    }
    else
    {
      privmsg("  error: unable to retrive SN uid for user \"$trailing\"");
    }
    break;
  case "name":
    $name=get_name($trailing);
    if ($name!==False)
    {
      privmsg("  SN username for uid $trailing is \"$name\"");
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
      $uid=get_uid($trailing);
      if ($uid!==False)
      {
        $uname=$trailing;
        $trailing=$uid;
      }
      else
      {
        privmsg("  error: invalid uid");
        break;
      }
    }
    else
    {
      $name=get_name($trailing);
      if ($name!==False)
      {
        $uname=$name;
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

function get_uid($name)
{
  global $host;
  global $port;
  $uri="/api.pl?m=user&op=get_uid&nick=".urlencode($name);
  $response=wget($host,$uri,$port);
  $content=strip_headers($response);
  $data=json_decode($content,True);
  if (isset($data["uid"])==True)
  {
    return $data["uid"];
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function get_name($uid)
{
  global $host;
  global $port;
  $uri="/api.pl?m=user&op=get_nick&uid=$uid";
  $response=wget($host,$uri,$port);
  $content=strip_headers($response);
  $data=json_decode($content,True);
  if (isset($data["nick"])==True)
  {
    return $data["nick"];
  }
  else
  {
    return False;
  }
}

#####################################################################################################

?>
