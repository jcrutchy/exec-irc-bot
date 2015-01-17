<?php

#####################################################################################################

/*
exec:~link|10|0|0|1|*||||php scripts/link.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];

if ($trailing=="")
{
  return;
}

$list=get_array_bucket("~link/list");
$parts=explode(" ",$trailing);
if (count($parts)==2)
{
  if ($parts[1]=="-")
  {
    if (isset($list[$parts[0]])==True)
    {
      unset($list[$parts[0]]);
      privmsg("  link unset");
    }
    else
    {
      privmsg("  error: link not found");
    }
  }
  else
  {
    $list[$parts[0]]=$parts[1];
    privmsg("  link set");
  }
  set_array_bucket($list,"~link/list");
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
  if ((substr($trailing,0,1)<>substr($trailing,strlen($trailing)-1,1)) or (strlen($trailing)==1))
  {
    $trailing="~".$trailing."~";
  }
  $results=preg_match_keys($trailing,$list);
  $n=count($results);
  if ($n>0)
  {
    $i=0;
    foreach ($results as $key => $value)
    {
      if ($i==($n-1))
      {
        privmsg("  └─ $key => $value");
      }
      else
      {
        privmsg("  ├─ $key => $value");
      }
      $i++;
    }
  }
  else
  {
    privmsg("  error: no links match");
  }
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

?>
