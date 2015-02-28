<?php

#####################################################################################################

/*
exec:~link|10|0|0|1|*||||php scripts/link.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~links|10|0|0|1|*||||php scripts/link.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~!|10|0|0|1|*||||php scripts/link.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg("syntax to search: $alias %search%, set: $alias %id% %content%, delete: $alias %id% -");
  privmsg("can't use pipe (|) char, %id% can't contain spaces, but %content% can, %search% is a regexp pattern");
  privmsg("will return a list of one or more %id% => %content% if %search% matches either %id% or %content%");
  return;
}

$list=load_settings(DATA_PATH."links","|");
ksort($list);
$parts=explode(" ",$trailing);
if (count($parts)>=2)
{
  if ((count($parts)==2) and ($parts[1]=="-"))
  {
    if (isset($list[$parts[0]])==True)
    {
      unset($list[$parts[0]]);
      privmsg("  └─ deleted ".$parts[0]);
    }
    else
    {
      privmsg("  └─ ".$parts[0]." not found");
    }
  }
  else
  {
    $id=$parts[0];
    array_shift($parts);
    $content=implode(" ",$parts);
    if ((strpos($id,"|")===False) and (strpos($content,"|")===False))
    {
      $list[$id]=$content;
      privmsg("  └─ $id => ".$list[$id]);
    }
    else
    {
      privmsg("  └─ error: can't contain pipe (|) character");
    }
  }
  save_settings($list,DATA_PATH."links","|");
}
else
{
  if (isset($list[$trailing])==True)
  {
    $value=$list[$trailing];
    privmsg("  └─ $trailing => $value");
    return;
  }
  # TODO: ALLOW USE OF PCRE DELIMITERS & MODIFIERS
  # http://php.net/manual/en/reference.pcre.pattern.syntax.php
  # http://php.net/manual/en/reference.pcre.pattern.modifiers.php
  /*if ((substr($trailing,0,1)<>substr($trailing,strlen($trailing)-1,1)) or (strlen($trailing)==1))
  {
    $trailing="~".$trailing."~";
  }*/
  $trailing="~".$trailing."~";
  $results=array_merge(preg_match_keys($trailing,$list),preg_match_values($trailing,$list));
  $n=count($results);
  if ($n>0)
  {
    $w=max_key_len($results);
    $i=0;
    foreach ($results as $key => $value)
    {
      if ($i==($n-1))
      {
        privmsg("  └─ ".str_pad($key,$w)." => $value");
      }
      else
      {
        privmsg("  ├─ ".str_pad($key,$w)." => $value");
      }
      $i++;
    }
  }
  else
  {
    privmsg("  └─ \"".trim($argv[1])."\" not found");
  }
}

#####################################################################################################

function max_key_len($array)
{
  $result=0;
  foreach ($array as $key => $value)
  {
    $result=max($result,strlen($key));
  }
  return $result;
}

#####################################################################################################

function preg_match_keys($pattern,$subject)
{
  $result=array();
  foreach ($subject as $key => $value)
  {
    if (preg_match($pattern,$key)==1)
    {
      $result[$key]=$value;
    }
  }
  return $result;
}

#####################################################################################################

function preg_match_values($pattern,$subject)
{
  $result=array();
  foreach ($subject as $key => $value)
  {
    if (preg_match($pattern,$value)==1)
    {
      $result[$key]=$value;
    }
  }
  return $result;
}

#####################################################################################################

?>
