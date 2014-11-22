<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~github-list|60|0|0|1||||0|php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~github-feed|280|300|0|1||||0|php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~slashcode-issue|60|0|0|1|*|||0|php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
startup:~join #github
*/

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=strtolower(trim($argv[4]));

if ($alias=="~slashcode-issue")
{
  $host="api.github.com";
  $port=443;
  # SoylentNews/slashcode
  $username="crutchy-";
  $repo="exec-irc-bot";
  $uri="/repos/$username/$repo/issues";
  $tok=trim(file_get_contents("../pwd/gh_tok"));
  $headers=array();
  $headers["Authorization"]="token $tok";
  $headers["Content-Type"]="application/json";
  $headers["Accept"]="application/vnd.github.v3+json";
  $params=array();
  $params["title"]="test title";
  $params["body"]="test body";
  $json=json_encode($params,JSON_PRETTY_PRINT);
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$json,$headers,60,True,True);
  $content=strip_headers($response);
  $data=json_decode($content,True);
  var_dump($response);
  return;
}

define("FEED_CHAN","#github");

$list=array(
  "crutchy-/exec-irc-bot",
  "TheMightyBuzzard/slashcode",
  "TheMightyBuzzard/api-testing",
  "chromatos/pas",
  "Subsentient/aqu4bot",
  "Subsentient/epoch",
  "Subsentient/bricktick",
  "Subsentient/wzblue",
  "SoylentNews/slashcode",
  "SoylentNews/slashcode_vm",
  "paulej72/slashcode",
  "NCommander/slashcode",
  "arachnist/dsd",
  "arachnist/repost",
  "mrcoolbp/slashcode",
  "pipedot/pipecode",
  "Lagg/userscripts",
  "Lagg/dotfiles",
  "Lagg/steamodd",
  "Lagg/c3-code",
  "Lagg/tinyfeeds",
  "Lagg/weechat-scripts",
  "Lagg/steam-tracker",
  "Lagg/steam-swissapiknife");

sort($list);

define("TIME_LIMIT_SEC",300); # 5 mins
define("CREATE_TIME_FORMAT","Y-m-d H:i:s ");

if ($alias=="~github-list")
{
  for ($i=0;$i<count($list);$i++)
  {
    notice($nick,$list[$i]);
  }
  return;
}

for ($i=0;$i<count($list);$i++)
{
  check_push_events($list[$i]);
  check_pull_events($list[$i]);
  check_issue_events($list[$i]);
}

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
          pm(FEED_CHAN,chr(3)."11"."  ".$commit["author"]["name"].": ".$commit["message"]);
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
              $html_url=$commit_data["html_url"];
              pm(FEED_CHAN,chr(3)."11"."  ".$html_url);
              $n1=count($commit_data["files"]);
              for ($k=0;$k<$n1;$k++)
              {
                if ($k>4)
                {
                  $rem=$n1-$k;
                  pm(FEED_CHAN,"  ".chr(3)."08"."└─".chr(3)."($rem files skipped)");
                  break;
                }
                $commit_filename=str_replace(" ","%20",$commit_data["files"][$k]["filename"]);
                $commit_status=$commit_data["files"][$k]["status"];
                $tree_symbol="├─";
                if ($k==($n1-1))
                {
                  $tree_symbol="└─";
                }
                if ($commit_status=="removed")
                {
                  pm(FEED_CHAN,"  ".chr(3)."08".$tree_symbol."removed:".chr(3)." /$repo/blob/$branch/$commit_filename");
                }
                else
                {
                  $commit_changes="";
                  if ((isset($commit_data["files"][$k]["additions"])==True) and (isset($commit_data["files"][$k]["deletions"])==True))
                  {
                    $additions=$commit_data["files"][$k]["additions"];
                    $deletions=$commit_data["files"][$k]["deletions"];
                    $commit_changes=" [+$additions,-$deletions]";
                  }
                  pm(FEED_CHAN,"  ".chr(3)."08".$tree_symbol.$commit_status.$commit_changes.":".chr(3)." https://github.com/$repo/blob/$branch/$commit_filename");
                }
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
      pm(FEED_CHAN,chr(3)."08"."└─".chr(3).$data[$i]["body"]);
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
  $tok=trim(file_get_contents("../pwd/gh_tok"));
  $headers=array();
  $headers["Authorization"]="token $tok";
  $headers["Accept"]="application/vnd.github.v3+json";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$headers,60);
  $content=strip_headers($response);
  return json_decode($content,True);
}

#####################################################################################################

?>
