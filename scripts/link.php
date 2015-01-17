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

$list=get_array_bucket("~links/list");
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
  set_array_bucket($list,"~links/list");
}
else
{
  if (isset($list[$trailing])==True)
  {
    privmsg("  ".$list[$trailing]);
  }
  else
  {
    privmsg("  error: link not found");
  }
}

#####################################################################################################

function wild_search_keys($array,$query)
{
  $result=array();
  foreach ($array as $key => $value)
  {
    if (wild_compare($key,$query)==True)
    {
      $result[$key]=$value;
    }
  }
  return $result;
}

#####################################################################################################

# subject = "my dog has fleas"
# query = "*d*g*"

function wild_compare($subject,$query)
{
  
}

#####################################################################################################

?>
