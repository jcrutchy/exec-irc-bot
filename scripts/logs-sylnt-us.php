<?php

#####################################################################################################

/*
#exec:~count|0|0|0|1|||||php scripts/logs-sylnt-us.php %%trailing%% %%nick%% %%dest%% %%alias%%
#exec:~first|0|0|0|1|||||php scripts/logs-sylnt-us.php %%trailing%% %%nick%% %%dest%% %%alias%%
#exec:~last|0|0|0|1|||||php scripts/logs-sylnt-us.php %%trailing%% %%nick%% %%dest%% %%alias%%
#exec:~find-first|0|0|0|1|||||php scripts/logs-sylnt-us.php %%trailing%% %%nick%% %%dest%% %%alias%%
#exec:~find-last|0|0|0|1|||||php scripts/logs-sylnt-us.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

date_default_timezone_set("UTC");
require_once("lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);
$dest=strtolower($argv[3]);
$alias=$argv[4];

$uri="/".urlencode($dest)."/index.html";
$html=wget("logs.sylnt.us",$uri,80);
$html=strip_headers($html);

strip_all_tag($html,"head");

$links=explode("<a href=\"",$html);
array_shift($links);
$links2=array();
for ($i=0;$i<count($links);$i++)
{
  $parts=explode("\">",$links[$i]);
  if (strpos($parts[0],"html")!==False)
  {
    $links2[]=$parts[0];
  }
}

$privmsg=array();

$date_today=floor(time()/60/60/24);

for ($i=0;$i<count($links2);$i++)
{
  $date_parts=explode(".html",$links2[$i]);
  $date_file=round(convert_timestamp($date_parts[0],"Y-m-d")/60/60/24);
  $cache_filename=CACHE_PATH.urldecode($dest)."_".$date_parts[0].".txt";
  $lines2=array();
  $status_fn="";
  #term_echo("*** date_file = $date_file, date_today = $date_today, link = ".$links2[$i]." ***");
  if ((file_exists($cache_filename)==True) and ($date_file<$date_today))
  {
    $status_fn=$cache_filename;
    $lines2=explode("\n",file_get_contents($cache_filename));
  }
  else
  {
    #term_echo("*** downloading log: ".$links2[$i]." ***");
    $uri="/".urlencode($dest)."/".$links2[$i];
    $status_fn="http://logs.sylnt.us".$uri;
    $html=wget("logs.sylnt.us",$uri,80);
    $html=strip_headers($html);
    strip_all_tag($html,"head");
    $lines=explode("class=\"time\">",$html);
    array_shift($lines);
    $lines2=array();
    for ($j=0;$j<count($lines);$j++)
    {
      $line=trim($lines[$j]);
      $line=strip_tags($line);
      $k=strpos($line,"\n");
      if ($k!==False)
      {
        $line=trim(substr($line,0,$k));
      }
      # [11:36:41] &lt;crutchy&gt; blah test
      $delim="[";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line=trim(substr($line,$k+strlen($delim)));
      # 11:36:41] &lt;crutchy&gt; blah test
      $delim="]";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line_time=substr($line,0,$k);
      $line=trim(substr($line,$k+strlen($delim)));
      # &lt;crutchy&gt; blah test
      $delim="&lt;";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line=trim(substr($line,$k+strlen($delim)));
      # crutchy&gt; blah test
      $delim="&gt;";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line_nick=substr($line,0,$k);
      $line=trim(substr($line,$k+strlen($delim)));
      # blah test
      $line_msg=$line;
      $line_dest=$dest;
      $line_date=$date_parts[0];

      $items=array();
      $items["server"]="irc.sylnt.us";
      $items["microtime"]=convert_timestamp($record["date"]." ".$record["time"],"Y-m-d H:i:s");;
      $items["time"]="";
      $items["data"]="";
      $items["prefix"]="";
      $items["params"]=$line_dest;
      $items["trailing"]="";
      $items["nick"]=$line_nick;
      $items["user"]="";
      $items["hostname"]="";
      $items["destination"]=$line_dest;
      $items["cmd"]="PRIVMSG";

      $record=array();
      $record["dest"]=$line_dest;
      $record["date"]=$line_date;
      $record["time"]=$line_time;
      $record["nick"]=$line_nick;
      $record["msg"]=$line_msg;
      $record["timestamp"]=convert_timestamp($record["date"]." ".$record["time"],"Y-m-d H:i:s");
      $lines2[$j]=serialize($record);
    }
    if ($date_file<$date_today)
    {
      file_put_contents($cache_filename,implode("\n",$lines2));
    }
  }
  $privmsg=array_merge($privmsg,$lines2);
  #term_echo("processing \"$status_fn\" => lines: ".count($lines2)." [$date_file : $date_today]");
}

$privmsg2=array();
for ($i=0;$i<count($privmsg);$i++)
{
  $privmsg2[]=unserialize($privmsg[$i]);
}

$this_msg=$alias." ".$trailing;

if ($trailing<>"")
{
  $privmsg_nick=array();
  $ltrailing=strtolower($trailing);
  for ($i=0;$i<count($privmsg);$i++)
  {
    if (($privmsg2[$i]["nick"]==$nick) and ($privmsg2[$i]["msg"]==$this_msg))
    {
      continue;
    }
    if (strtolower($privmsg2[$i]["nick"])==$ltrailing)
    {
      $privmsg_nick[]=$privmsg2[$i];
    }
  }
  switch ($alias)
  {
    case "~count":
      if ($trailing<>"")
      {
        if ($trailing=="*")
        {
          if (count($privmsg2)>0)
          {
            privmsg("total privmsgs in all logged channels since ".$privmsg2[0]["date"]." @ ".$privmsg2[0]["time"].": ".count($privmsg2));
          }
          else
          {
            privmsg("no privmsgs");
          }
        }
        else
        {
          if (count($privmsg_nick)>0)
          {
            $t1=$privmsg_nick[0]["timestamp"];
            $t2=$privmsg_nick[count($privmsg_nick)-1]["timestamp"];
            $days=round(($t2-$t1)/60/60/24);
            $n=count($privmsg_nick);
            $avg=round($n/$days);
            privmsg("privmsg count for $trailing in $dest: $n over $days days ($avg per day avg)");
          }
          else
          {
            privmsg("no privmsgs for $trailing in $dest exist");
          }
        }
      }
      else
      {
        term_echo("*** SN LOG: COUNTING PRIVMSGS FOR CHANNEL $dest");
        $results=array();
        for ($i=0;$i<count($privmsg2);$i++)
        {
          if ($privmsg2[$i]["dest"]==$dest)
          {
            $results[]=$privmsg2[$i];
          }
        }
        if (count($results)>0)
        {
          privmsg("total privmsgs in $dest since ".$results[0]["date"]." @ ".$results[0]["time"].": ".count($results));
        }
        else
        {
          privmsg("no privmsgs in $dest");
        }
      }
      break;
    case "~first":
      if (count($privmsg_nick)>0)
      {
        $record=$privmsg_nick[0];
        privmsg("first privmsg for $trailing in $dest: [".$record["date"]." ".$record["time"]."] ".$record["msg"]);
      }
      else
      {
        privmsg("first privmsg for $trailing in $dest not found");
      }
      break;
    case "~last":
      if (count($privmsg_nick)>0)
      {
        $record=$privmsg_nick[count($privmsg_nick)-1];
        privmsg("last privmsg for $trailing in $dest: [".$record["date"]." ".$record["time"]."] ".$record["msg"]);
      }
      else
      {
        privmsg("last privmsg for $trailing in $dest not found");
      }
      break;
    case "~find-last":
      $last=count($privmsg2)-1;
      for ($i=$last;$i>=0;$i--)
      {
        if (($privmsg2[$i]["nick"]==$nick) and ($privmsg2[$i]["msg"]==$this_msg))
        {
          continue;
        }
        $lmsg=strtolower($privmsg2[$i]["msg"]);
        if (strpos($lmsg,$ltrailing)!==False)
        {
          $msg=html_entity_decode($privmsg2[$i]["msg"],ENT_QUOTES,"UTF-8");
          # http://logs.sylnt.us/%23soylent/2014-03-15.html#00:03:26
          # urlencode($dest);
          privmsg("last privmsg containing \"$trailing\" in $dest: [".$privmsg2[$i]["date"]." ".$privmsg2[$i]["time"]."] <".$privmsg2[$i]["nick"]."> ".$msg);
          break;
        }
      }
      break;
    case "~find-first":
      $n=count($privmsg2);
      for ($i=0;$i<$n;$i++)
      {
        $lmsg=strtolower($privmsg2[$i]["msg"]);
        if (strpos($lmsg,$ltrailing)!==False)
        {
          $msg=html_entity_decode($privmsg2[$i]["msg"],ENT_QUOTES,"UTF-8");
          privmsg("first privmsg containing \"$trailing\" in $dest: [".$privmsg2[$i]["date"]." ".$privmsg2[$i]["time"]."] <".$privmsg2[$i]["nick"]."> ".$msg);
          break;
        }
      }
      break;
  }
}
else
{
  switch ($alias)
  {
    case "~chart":
      chart($privmsg2,$dest,"chart_$dest");
      break;
  }
}

#term_echo("log line count = ".count($privmsg));

#####################################################################################################

function register_loggie_channel($channel)
{
  $items=array();
  $items["server"]="irc.sylnt.us";
  $items["microtime"]="";
  $items["time"]="";
  $items["data"]="";
  $items["prefix"]="";
  $items["params"]="";
  $items["trailing"]="";
  $items["nick"]="";
  $items["user"]="";
  $items["hostname"]="";
  $items["destination"]="";
  $items["cmd"]="";

  $fieldnames=array_keys($items);
  $placeholders=array_map("callback_prepare",$fieldnames);
  $fieldnames=array_map("callback_quote",$fieldnames);
  execute_prepare("INSERT INTO ".BOT_SCHEMA.".".LOG_TABLE." (".implode(",",$fieldnames).") VALUES (".implode(",",$placeholders).")",$items);
}

#####################################################################################################

?>
