<?php

#####################################################################################################

/*
exec:~github-list|60|0|0|1|||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~github-feed|1700|1800|0|1|||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~slashcode-issue|60|0|0|1|*||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~slashcode-find|60|0|0|1|*||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~rehash-issue|60|0|0|1|*||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~exec-issue|60|0|0|1|*||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
#exec:~epoch-feed|1700|1800|0|1|||||php scripts/github_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
#startup:~join #github
*/

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("lib.php");

define("TIME_LIMIT_SEC",1800); # 30 mins

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=strtolower(trim($argv[4]));

if ($alias=="~slashcode-find")
{
  return;
}

if (($alias=="~slashcode-issue") or ($alias=="~rehash-issue") or ($alias=="~exec-issue"))
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","NCommander","juggs","TheMightyBuzzard","paulej72","Bytram");
  if (in_array($account,$allowed)==False)
  {
    return;
  }
  $parts=explode(",",$trailing);
  $title=trim($parts[0]);
  array_shift($parts);
  $body=trim(implode(",",$parts));
  if (($title=="") or ($body==""))
  {
    privmsg("syntax: ~slashcode/rehash/exec-issue <title>, <body>");
    return;
  }
  $host="api.github.com";
  $port=443;
  if ($alias=="~rehash-issue")
  {
    $username="SoylentNews";
    $repo="rehash";
  }
  elseif ($alias=="~slashcode-issue")
  {
    $username="SoylentNews";
    $repo="slashcode";
  }
  else
  {
    $username="crutchy-";
    $repo="exec-irc-bot";
  }
  $uri="/repos/$username/$repo/issues";
  $tok=trim(file_get_contents("../pwd/gh_tok"));
  $headers=array();
  $headers["Authorization"]="token $tok";
  $headers["Content-Type"]="application/json";
  $headers["Accept"]="application/vnd.github.v3+json";
  $params=array();
  $params["title"]=$title;
  $params["body"]=$body."\n\nsubmitted by exec on behalf of $nick from $dest @ irc.sylnt.us";
  $json=json_encode($params,JSON_PRETTY_PRINT);
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$json,$headers,60,True,False);
  $content=strip_headers($response);
  $data=json_decode($content,True);
  if (isset($data["html_url"])==True)
  {
    privmsg($data["html_url"]);
  }
  else
  {
    privmsg("there was an error submitting the issue");
  }
  return;
}

$list=exec_file_read(DATA_PATH."github_feed_repos");

/*$list=array(
  "crutchy-/exec-irc-bot",
  "crutchy-/ircd",
  "crutchy-/iiterm",
  "crutchy-/irciv",
  "crutchy-/conquest",
  "TheMightyBuzzard/slashcode",
  "TheMightyBuzzard/api-testing",
  "chromatos/pas",
  "Subsentient/aqu4bot",
  "Subsentient/epoch",
  "trohrt/kosaki-chan",
  "Dhs92/kosaki-chan",
  "Subsentient/bricktick",
  "Subsentient/wzblue",
  "Subsentient/nexus",
  "Subsentient/substrings",
  "marty-b/rehash",
  "marty-b/slashcode",
  "NSAKEY/happy-dance",
  "NSAKEY/not-paranoid-prosody",
  "NSAKEY/paranoid-prosody",
  "SoylentNews/slashcode",
  "SoylentNews/slashcode_vm",
  "SoylentNews/rehash",
  "davidtseng/rehash",
  "iwantedue/rehash",
  "NCommander/rehash",
  "paulej72/rehash",
  "TheMightyBuzzard/rehash",
  "cosurgi/trunk",
  "dimkr/LoginKit",
  "paulej72/slashcode",
  "NCommander/slashcode",
  "arachnist/dsd",
  "arachnist/repost",
  "idies/pyJHTDB",
  "mrcoolbp/slashcode",
  "pipedot/pipecode",
  "devuan/devuan-baseconf",
  "devuan/devuan-keyring",
  "devuan/website-debianfork",
  "lfowles/drunken-bugfixes",
  "lfowles/jsonbot",
  "lfowles/tripping-robot",
  "lfowles/shirokuma",
  "Lagg/userscripts",
  "Lagg/dotfiles",
  "Lagg/steamodd",
  "Lagg/c3-code",
  "Lagg/tinyfeeds",
  "Lagg/weechat-scripts",
  "Lagg/steam-tracker",
  "Lagg/steam-swissapiknife");*/

sort($list,SORT_STRING);

if ($alias=="~epoch-feed")
{
  $list=array("Subsentient/epoch");
  #$list=array("crutchy-/exec-irc-bot");
  define("FEED_CHAN","#epoch");
  #define("FEED_CHAN","#sylnt");
}
else
{
  define("FEED_CHAN","#github");
}

sort($list,SORT_STRING+SORT_FLAG_CASE);

define("CREATE_TIME_FORMAT","Y-m-d H:i:s ");

if ($alias=="~github-list")
{
  $gh_users=array();
  for ($i=0;$i<count($list);$i++)
  {
    $a=explode("/",$list[$i]);
    $gh_username=$a[0];
    $gh_repo=$a[1];
    if (isset($gh_users[$a[0]])==False)
    {
      $gh_users[$gh_username]=array();
    }
    $gh_users[$gh_username][]=$gh_repo;
  }
  ksort($gh_users,SORT_STRING+SORT_FLAG_CASE);
  foreach ($gh_users as $gh_username => $gh_repos)
  {
    sort($gh_repos,SORT_STRING+SORT_FLAG_CASE);
    privmsg(chr(3)."03".$gh_username." => ".implode(", ",$gh_repos));
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
    if ($data[$i]["type"]<>"PushEvent")
    {
      continue;
    }
    $timestamp=$data[$i]["created_at"];
    $t=convert_timestamp($timestamp,CREATE_TIME_FORMAT);
    $dt=microtime(True)-$t;
    if ($dt>TIME_LIMIT_SEC)
    {
      continue;
    }
    github_msg($repo,chr(3)."13"."push to https://github.com/$repo @ ".date("H:i:s",$t)." by ".$data[$i]["actor"]["login"]);
    github_msg($repo,"  ".chr(3)."03".$data[$i]["payload"]["ref"]);
    for ($j=0;$j<count($data[$i]["payload"]["commits"]);$j++)
    {
      $commit=$data[$i]["payload"]["commits"][$j];
      github_msg($repo,chr(3)."11"."  ".$commit["author"]["name"].": ".chr(2).chr(3)."03".$commit["message"]);
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
          github_msg($repo,chr(3)."11"."  ".$html_url);
          $n1=count($commit_data["files"]);
          for ($k=0;$k<$n1;$k++)
          {
            if ($k>4)
            {
              $rem=$n1-$k;
              github_msg($repo,"  ".chr(3)."08"."└─".chr(3)."($rem files skipped)");
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
              github_msg($repo,"  ".chr(3)."08".$tree_symbol."removed:".chr(3)." /$repo/blob/$branch/$commit_filename");
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
              github_msg($repo,"  ".chr(3)."08".$tree_symbol.$commit_status.$commit_changes.":".chr(3)." https://github.com/$repo/blob/$branch/$commit_filename");
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
      github_msg($repo,chr(3)."13"."pull request by ".$data[$i]["user"]["login"]." @ ".date("H:i:s",$t)." - ".$data[$i]["_links"]["html"]["href"]);
      github_msg($repo,chr(3)."08"."└─".chr(3).$data[$i]["body"]);
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
      github_msg($repo,chr(3)."13"."issue ".$data[$i]["event"]." by ".$data[$i]["actor"]["login"]." @ ".date("H:i:s",$t)." - ".$data[$i]["issue"]["html_url"]);
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

function github_msg($repo,$msg)
{
  pm(FEED_CHAN,$msg);
  /*if ((strpos(strtolower($repo),"slashcode")!==False) or (strpos(strtolower($repo),"soylentcode")!==False))
  {
    pm("#dev",$msg);
  }*/
}

#####################################################################################################

?>
