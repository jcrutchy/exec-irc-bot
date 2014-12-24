<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~bitbucket-feed|60|300|0|1|||||php scripts/bitbucket_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

# https://confluence.atlassian.com/display/BITBUCKET/Use+the+Bitbucket+REST+APIs
# https://bitbucket.org/api/1.0/repositories/bcsd/uselessd/events

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=strtolower(trim($argv[4]));

define("FEED_CHAN","#github");

$list=array(
  "bcsd/uselessd");

define("TIME_LIMIT_SEC",300); # 5 mins

if ($alias=="~bitbucket")
{
  for ($i=0;$i<count($list);$i++)
  {
    check_push_events_bitbucket($list[$i]);
  }
}

#####################################################################################################

function check_push_events_bitbucket($repo)
{
  $data=get_api_data("/api/1.0/repositories/$repo/events","bitbucket");
  $changesets=get_api_data("/api/1.0/repositories/$repo/changesets?limit=10","bitbucket");
  for ($i=0;$i<count($data["events"]);$i++)
  {
    if (isset($data["events"][$i]["utc_created_on"])==False)
    {
      continue;
    }
    $timestamp=$data["events"][$i]["utc_created_on"];
    $t=convert_timestamp($timestamp,"Y-m-d H:i:s      ");
    $dt=microtime(True)-$t;
    if ($dt<=TIME_LIMIT_SEC)
    {
      if ($data["events"][$i]["event"]=="pushed")
      {
        pm(FEED_CHAN,chr(3)."13"."push to https://bitbucket.org/$repo @ ".date("H:i:s",$t)." by ".$data["events"][$i]["user"]["username"]);
        $commits=$data["events"][$i]["description"]["commits"];
        for ($j=0;$j<count($commits);$j++)
        {
          $changeset=bitbucket_get_changeset($changesets,$commits[$j]["hash"]);
          if ($changeset===False)
          {
            pm(FEED_CHAN,"changeset not found");
            continue;
          }
          $desc=$commits[$j]["description"];
          if ($desc<>$changeset["message"])
          {
            continue;
          }
          pm(FEED_CHAN,chr(3)."11"."  ".$changeset["author"].": ".$changeset["message"]);
          $url="https://bitbucket.org/$repo/commits/".$commits[$j]["hash"];
          pm(FEED_CHAN,chr(3)."11"."  ".$url);
        }
      }
    }
  }
}

#####################################################################################################

function bitbucket_get_changeset(&$changesets,$hash)
{
  for ($i=0;$i<count($changesets["changesets"]);$i++)
  {
    $raw_node=$changesets["changesets"][$i]["raw_node"];
    if ($hash==$raw_node)
    {
      return $changesets["changesets"][$i];
    }
  }
  return False;
}

#####################################################################################################

function get_api_data($uri)
{
  $host="bitbucket.org";
  $port=443;
  $headers="";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$headers,60);
  $content=strip_headers($response);
  return json_decode($content,True);
}

#####################################################################################################

?>
