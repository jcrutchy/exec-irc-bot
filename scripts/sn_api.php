<?php

#####################################################################################################

/*
exec:~api|90|0|0|1|*|PRIVMSG|#dev,#Soylent,#,#journals||php scripts/sn_api.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
help: ~api | example 1: ~api m=user op=get_nick uid=18 /nick
help: ~api | example 2: ~api m=user op=get_uid nick=The Mighty Buzzard /uid
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

define("API_WIKI_URL","sylnt.us/api");

if ($trailing=="")
{
  privmsg(API_WIKI_URL);
  return;
}
$params=parse_parameters($trailing,"="," ",False);
if ($params!==False)
{
  foreach ($params as $key => $value)
  {
    if (strpos($key," ")!==False)
    {
      $params=False;
      break;
    }
  }
}
if ($params===False)
{
  privmsg(API_WIKI_URL);
  return;
}
$element="";
$paramstr="";
foreach ($params as $key => $value)
{
  $parts=explode("/",$value);
  if (count($parts)>1)
  {
    $value=trim($parts[0]);
    array_shift($parts);
    $element=implode("/",$parts);
  }
  if ($paramstr<>"")
  {
    $paramstr=$paramstr."&";
  }
  $paramstr=$paramstr.urlencode($key)."=".urlencode($value);
}
$uri="/api.pl?".$paramstr;
$host="soylentnews.org";
var_dump($host.$uri);
$port=443;
$response=wget($host,$uri,$port,ICEWEASEL_UA,"",20,"",1024,False);
$content=trim(strip_headers($response));
if ($content=="")
{
  privmsg("  no data returned");
  return;
}
if ($element=="")
{
  $content=clean_text($content);
  privmsg(chr(3)."02".substr($content,0,650));
}
else
{
  $data=json_decode($content,True);
  #$result=eval("return \$data$element;");
  #privmsg(chr(3)."02".substr($result,0,650));
  #return;
  if (isset($data[$element])==True)
  {
    privmsg(chr(3)."02".substr($data[$element],0,650));
  }
  else
  {
    privmsg(chr(3)."02  api error: element \"$element\" not found");
    #var_dump($data);
  }
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
