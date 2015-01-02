<?php

#####################################################################################################

define("LOG_PATH","/var/www/irciv.us.to/exec_logs/");
define("BOT_PATH","/var/include/vhosts/irciv.us.to/inc/");
define("TEMPLATE_FILE",__DIR__."/stats_template");
define("IRC_LIB_FILE",BOT_PATH."irc_lib.php");
define("EXEC_FILE",BOT_PATH."exec.txt");
define("CMD_BUCKET_GET","BUCKET_GET");
define("CMD_BUCKET_SET","BUCKET_SET");
define("CMD_BUCKET_UNSET","BUCKET_UNSET");
define("CMD_INTERNAL","INTERNAL");
define("EXEC_DELIM","|");
define("NICK","exec");

require_once(IRC_LIB_FILE);

$file_list=get_file_list(LOG_PATH);
$valid_data_cmd=get_valid_data_cmd();

$exec_list=exec_load();
$internal_aliases=array("~list","~list-auth","~lock","~unlock","~q","~reload","~dest-override","~dest-clear","~buckets-dump","~buckets-save","~buckets-load","~buckets-flush","~buckets-list","~restart");

$stats_items=array();

$ts_start="";
$ts_end="";

$n=count($file_list);
for ($i=0;$i<$n;$i++)
{
  $parts=explode(".",$file_list[$i]);
  if ((is_numeric($parts[0])==True) and (strlen($parts[0])==8))
  {
    $content=file_get_contents(LOG_PATH.$file_list[$i]);
    $lines=explode("\n",$content);
    $c=count($lines);
    for ($j=0;$j<$c;$j++)
    {
      $line_parts=explode(">> ",$lines[$j]);
      $timestamp=substr($line_parts[0],2);
      if (count($line_parts)<2)
      {
        continue;
      }
      array_shift($line_parts);
      $data=implode(">> ",$line_parts);
      $items=parse_data($data);
      if ($items===False)
      {
        continue;
      }
      if (($items["nick"]==NICK) or ($items["nick"]==""))
      {
        continue;
      }
      $trailing_parts=explode(" ",$items["trailing"]);
      $alias=strtolower(trim($trailing_parts[0]));
      if ((isset($exec_list[$alias])==False) or ($alias=="~sed-internal"))
      {
        continue;
      }
      array_shift($parts);
      $trailing=implode(" ",$parts);
      $items["nick"]=strtolower($items["nick"]);
      $items["alias"]=$alias;
      $items["alias_trailing"]=$trailing;
      $items["timestamp"]=$timestamp;
      $stats_items[]=$items;
      if ($ts_start=="")
      {
        $ts_start=$timestamp;
      }
      else
      {
        if ($timestamp<$ts_start)
        {
          $ts_start=$timestamp;
        }
      }
      if ($ts_end=="")
      {
        $ts_end=$timestamp;
      }
      else
      {
        if ($timestamp>$ts_end)
        {
          $ts_end=$timestamp;
        }
      }
    }
  }
}
$item_count=count($stats_items);

$stats=array();

$nicks_arr=array();
for ($i=0;$i<$item_count;$i++)
{
  $nick=$stats_items[$i]["nick"];
  $alias=$stats_items[$i]["alias"];
  if (isset($stats[$alias][$nick])==False)
  {
    $stats[$alias][$nick]=1;
  }
  else
  {
    $stats[$alias][$nick]=$stats[$alias][$nick]+1;
  }
  if (in_array($nick,$nicks_arr)==False)
  {
    $nicks_arr[]=$nick;
  }
}
$nicks_str="";
if (count($nicks_arr)>0)
{
  $nicks_str=implode("</th><th>",$nicks_arr);
}

$rows=array();
foreach ($exec_list as $alias => $data)
{
  $enc_alias=str_replace("<","&lt;",$alias);
  $enc_alias=str_replace(">","&gt;",$enc_alias);
  $row="<tr><td>".$enc_alias."</td>";
  for ($i=0;$i<count($nicks_arr);$i++)
  {
    $nick=$nicks_arr[$i];
    if (isset($stats[$alias][$nick])==False)
    {
      $row=$row."<td>0</td>";
    }
    else
    {
      $row=$row."<td>".$stats[$alias][$nick]."</td>";
    }
  }
  $row=$row."</tr>";
  $rows[]=$row;
}

$rows_str=implode("\n",$rows);

$page=file_get_contents(TEMPLATE_FILE);
$page=str_replace("%%item_count%%",$item_count,$page);
$page=str_replace("%%ts_start%%",$ts_start,$page);
$page=str_replace("%%ts_end%%",$ts_end,$page);
$page=str_replace("%%nicks%%",$nicks_str,$page);
$page=str_replace("%%rows%%",$rows_str,$page);
echo $page;

#####################################################################################################

function get_file_list($path)
{
  $result=array();
  if ((file_exists($path)===True) and (is_dir($path)===True))
  {
    $handle=opendir($path);
    while(($file=readdir($handle))!==False)
    {
      $filename=$path.$file;
      if(($file<>".") and ($file<>"..") and (is_dir($filename)===False))
      {
        $result[]=$file;
      }
    }
    closedir($handle);
  }
  return $result;
}

#####################################################################################################

?>
