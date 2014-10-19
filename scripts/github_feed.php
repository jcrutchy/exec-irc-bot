<?php

# gpl2
# by crutchy

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=strtolower(trim($argv[4]));

define("FEED_CHAN","#github");

$list=array(
  "crutchy-/exec-irc-bot",
  "TheMightyBuzzard/slashcode",
  "chromatos/pas",
  "Subsentient/aqu4bot",
  "SoylentNews/slashcode",
  "paulej72/slashcode",
  "NCommander/slashcode",
  "arachnist/dsd",
  "arachnist/repost",
  "mrcoolbp/slashcode",
  "pipedot/pipecode",
  "Lagg/steamodd",
  "SoylentNews/slashcode_vm");

define("TIME_LIMIT_SEC",300); # 5 mins
define("CREATE_TIME_FORMAT","Y-m-d H:i:s ");

if ($alias=="~github-list")
{
  for ($i=0;$i<count($list);$i++)
  {
    privmsg($list[$i]);
  }
  return;
}

if ($alias=="~github-atom")
{
  $host="api.github.com";
  $port=443;
  $uri="/crutchy-";
  $tok=file_get_contents("../pwd/gh_tok");
  $headers=array();
  $headers["Authorization"]="token $tok";
  $headers["Accept"]="application/atom+xml";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$headers,60);
  var_dump($response);
  return;
}

for ($i=0;$i<count($list);$i++)
{
  check_push_events($list[$i]);
  check_pull_events($list[$i]);
  check_issue_events($list[$i]);
}

/*$users=array();
for ($i=0;$i<count($list);$i++)
{
  $user=substr($list[$i],0,strpos($list[$i],"/"));
  if (in_array($user,$users)==False)
  {
    $users[]=$user;
  }
}
for ($i=0;$i<count($users);$i++)
{
  check_events(11,"/users/".$users[$i]."/events");
}*/

#check_events(11,"/events");

#####################################################################################################

function check_events($color,$uri)
{
  $repos_url="https://api.github.com/repos/";
  $len_repos_url=strlen($repos_url);
  $data=get_api_data($uri);
  $n=count($data)-1;
  for ($i=$n;$i>=0;$i--)
  {
    if (isset($data[$i]["created_at"])==False)
    {
      continue;
    }
    $timestamp=$data[$i]["created_at"];
    $t=convert_timestamp($timestamp,CREATE_TIME_FORMAT);
    $dt=microtime(True)-$t;
    if ($dt<=TIME_LIMIT_SEC)
    {
      if ((isset($data[$i]["type"])==False) or (isset($data[$i]["actor"]["login"])==False) or (isset($data[$i]["repo"]["url"])==False))
      {
        continue;
      }
      if (substr($data[$i]["repo"]["url"],0,$len_repos_url)<>$repos_url)
      {
        continue;
      }
      $url="https://github.com/".substr($data[$i]["repo"]["url"],$len_repos_url);
      pm(FEED_CHAN,chr(3).$color.$data[$i]["type"]." by ".$data[$i]["actor"]["login"]." @ $url");
    }
  }
}

#####################################################################################################

function check_push_events($repo)
{
  $data=get_api_data("/repos/$repo/events");
  $n=count($data)-1;
  for ($i=$n;$i>=0;$i--)
  {
    if (isset($data[$i]["created_at"])==False)
    {
      continue;
    }
    $timestamp=$data[$i]["created_at"];
    $t=convert_timestamp($timestamp,CREATE_TIME_FORMAT);
    $dt=microtime(True)-$t;
    if ($dt<=TIME_LIMIT_SEC)
    {
      if ($data[$i]["type"]=="PushEvent")
      {
        pm(FEED_CHAN,chr(3)."13"."push to https://github.com/$repo @ ".date("H:i:s",$t)." by ".$data[$i]["actor"]["login"]);
        pm(FEED_CHAN,"  ".chr(3)."03".$data[$i]["payload"]["ref"]);
        for ($j=0;$j<count($data[$i]["payload"]["commits"]);$j++)
        {
          $commit=$data[$i]["payload"]["commits"][$j];
          pm(FEED_CHAN,"  ".$commit["author"]["name"].": ".$commit["message"]);
          $commit_url=$commit["url"];
          $commit_host="";
          $commit_uri="";
          $commit_port="";
          if (get_host_and_uri($commit_url,$commit_host,$commit_uri,$commit_port)==True)
          {
            $commit_data=get_api_data($commit_uri);
            $ref_parts=explode("/",$data[$i]["payload"]["ref"]);
            if ((isset($commit_data["files"])==True) and (isset($ref_parts[2])==True))
            {
              $branch=$ref_parts[2];
              for ($k=0;$k<count($commit_data["files"]);$k++)
              {
                $commit_filename=$commit_data["files"][$k]["filename"];
                $tree_symbol="├";
                if ($k==(count($commit_data["files"])-1))
                {
                  $tree_symbol="└";
                }
                pm(FEED_CHAN,"  $tree_symbol https://github.com/$repo/blob/$branch/$commit_filename");
              }
            }
          }
        }
      }
    }
  }
}

#####################################################################################################

function check_pull_events($repo)
{
  $data=get_api_data("/repos/$repo/pulls");
  $n=count($data)-1;
  for ($i=$n;$i>=0;$i--)
  {
    if (isset($data[$i]["created_at"])==False)
    {
      continue;
    }
    $timestamp=$data[$i]["created_at"];
    $t=convert_timestamp($timestamp,CREATE_TIME_FORMAT);
    $dt=microtime(True)-$t;
    if ($dt<=TIME_LIMIT_SEC)
    {
      pm(FEED_CHAN,chr(3)."13"."pull request by ".$data[$i]["user"]["login"]." @ ".date("H:i:s",$t)." - ".$data[$i]["_links"]["html"]["href"]);
      pm(FEED_CHAN,"  ".$data[$i]["body"]);
    }
  }
}

#####################################################################################################

function check_issue_events($repo)
{
  $data=get_api_data("/repos/$repo/issues/events");
  $n=count($data)-1;
  for ($i=$n;$i>=0;$i--)
  {
    if (isset($data[$i]["created_at"])==False)
    {
      continue;
    }
    $timestamp=$data[$i]["created_at"];
    $t=convert_timestamp($timestamp,CREATE_TIME_FORMAT);
    $dt=microtime(True)-$t;
    if ($dt<=TIME_LIMIT_SEC)
    {
      pm(FEED_CHAN,chr(3)."13"."issue ".$data[$i]["event"]." by ".$data[$i]["actor"]["login"]." @ ".date("H:i:s",$t)." - ".$data[$i]["issue"]["html_url"]);
    }
  }
}

#####################################################################################################

function get_api_data($uri)
{
  $host="api.github.com";
  $port=443;
  $tok=file_get_contents("../pwd/gh_tok");
  $headers=array();
  $headers["Authorization"]="token $tok";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$headers,60);
  $content=strip_headers($response);
  return json_decode($content,True);
}

#####################################################################################################

?>
