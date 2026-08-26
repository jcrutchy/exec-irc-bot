<?php

#####################################################################################################

/*
exec:~api|90|0|0|1|*|PRIVMSG|#dev,#Soylent,#,#crutchy||php scripts/sn_api.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
help:~api syntax: ~api param1=value 1 param2=value 2 /depth 1/depth 2
help:~api example 2: ~api m=user op=get_uid nick=The Mighty Buzzard /uid
help:~api example 3: ~api m=story op=latest /0/title
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

$element=array();
$parts=explode("/",$trailing);
if (count($parts)>1)
{
  for ($i=1;$i<count($parts);$i++)
  {
    $element[]=$parts[$i];
  }
  $trailing=$parts[0];
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
$paramstr="";
foreach ($params as $key => $value)
{
  if ($paramstr<>"")
  {
    $paramstr=$paramstr."&";
  }
  $paramstr=$paramstr.urlencode($key)."=".urlencode($value);
}
$uri="/api.pl?".$paramstr;
$host="soylentnews.org";
privmsg($host.$uri);
$port=443;
$response=wget($host,$uri,$port,ICEWEASEL_UA,"",20,"",1024,False);
$content=trim(strip_headers($response));
if ($content=="")
{
  privmsg("  no data returned");
  return;
}
$data=json_decode($content,True);
if (count($element)==0)
{
  $data=json_encode($data,JSON_UNESCAPED_SLASHES);
  privmsg(chr(3)."02".substr($data,0,650));
}
else
{
  for ($i=0;$i<count($data);$i++)
  {
    if (isset($data[$element[$i]])==True)
    {
      $data=$data[$element[$i]];
    }
  }
  if (is_array($data)==True)
  {
    $data=json_encode($data,JSON_UNESCAPED_SLASHES);
  }
  privmsg(chr(3)."02".substr($data,0,650));
}

#####################################################################################################

?>
