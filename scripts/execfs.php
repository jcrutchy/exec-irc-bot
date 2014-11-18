<?php

# gpl2
# by crutchy

/*
exec:~var|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~rm|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~ls|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~cd|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");
require_once("execfs_lib.php");

$trailing=trim($argv[1]);
$nick=strtolower(trim($argv[2]));
$dest=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

if ($alias=="~var")
{
  $color="06";
  if ($trailing=="")
  {
    privmsg("syntax: ~var name=value");
    privmsg("if = is omitted, the value of name is returned");
    return;
  }
  $parts=explode("=",$trailing);
  $name=trim($parts[0]);
  $bucket=get_array_bucket("<<USER_VARS>>");
  $paths=get_array_bucket("<<USER_PATHS>>");
  if (isset($paths[$nick])==True)
  {
    $name=$paths[$nick].$name;
  }
  if (count($parts)==1)
  {
    if (isset($bucket[$name])==False)
    {
      privmsg(chr(3).$color."$name not found");
    }
    else
    {
      privmsg(chr(3).$color."$name = ".$bucket[$name]);
    }
  }
  else
  {
    array_shift($parts);
    $value=trim(implode("=",$parts));
    $bucket[$name]=$value;
    privmsg(chr(3).$color."$name = $value");
    set_array_bucket($bucket,"<<USER_VARS>>",True);
  }
  return;
}
if ($alias=="~rm")
{
  $name=trim($trailing);
  $bucket=get_array_bucket("<<USER_VARS>>");
  $paths=get_array_bucket("<<USER_PATHS>>");
  if (isset($paths[$nick])==True)
  {
    $name=$paths[$nick].$name;
  }
  if (isset($bucket[$name])==False)
  {
    privmsg(chr(3).$color."$name not found");
  }
  else
  {
    unset($bucket[$name]);
    privmsg(chr(3).$color."$name deleted");
  }
}
if ($alias=="~cd")
{
  $color="06";
  $paths=get_array_bucket("<<USER_PATHS>>");
  $path=trim($trailing);
  if ($path=="")
  {
    if (isset($paths[$nick])==True)
    {
      unset($paths[$nick]);
      privmsg(chr(3).$color."cleared path for $nick");
    }
    else
    {
      privmsg(chr(3).$color."path not found for $nick");
    }
  }
  else
  {
    $delim="";
    if (isset($paths[$nick])==True)
    {
      $delim=var_get_path_delim($paths[$nick]);
      if ($delim=="")
      {
        privmsg(chr(3).$color."invalid/no path delimiter");
        return;
      }
    }
    if ($path==($delim.$delim))
    {
      if (isset($paths[$nick])==True)
      {
        $path=$paths[$nick];
        if (substr($path,strlen($path)-1)==$delim)
        {
          $path=substr($path,0,strlen($path)-1);
        }
        $parts=explode($delim,$path);
        array_pop($parts);
        $path=implode($delim,$parts);
        if (substr($path,strlen($path)-1)<>$delim)
        {
          $path=$path.$delim;
        }
      }
      else
      {
        privmsg(chr(3).$color."path not found for $nick");
        return;
      }
    }
    else
    {
      $delim=var_get_path_delim($path);
      if (isset($paths[$nick])==True)
      {
        $delim=var_get_path_delim($paths[$nick]);
        if ($delim<>"")
        {
          if (strpos(trim($trailing),$delim)===False)
          {
            if (substr($path,strlen($path)-1)<>$delim)
            {
              $path=$path.$delim;
            }
            $path=$paths[$nick].trim($trailing);
          }
        }
      }
      if ($delim<>"")
      {
        if (strpos($path,$delim.$delim)!==False)
        {
          privmsg(chr(3).$color."invalid path");
          return;
        }
        if (substr($path,strlen($path)-1)<>$delim)
        {
          $path=$path.$delim;
        }
      }
    }
    $paths[$nick]=$path;
    privmsg(chr(3).$color."$nick@".NICK_EXEC.":$path");
  }
  set_array_bucket($paths,"<<USER_PATHS>>",True);
  return;
}
if ($alias=="~ls")
{
  $color="06";
  $paths=get_array_bucket("<<USER_PATHS>>");
  $bucket=get_array_bucket("<<USER_VARS>>");
  if (isset($paths[$nick])==True)
  {
    $output=array();
    foreach ($bucket as $name => $value)
    {
      if (substr($name,0,strlen($paths[$nick]))==$paths[$nick])
      {
        $output[]=$name;
      }
    }
    $n=count($output);
    if ($n==0)
    {
      privmsg(chr(3).$color."no vars found in ".$paths[$nick]);
    }
    else
    {
      for ($i=0;$i<count($output);$i++)
      {
        privmsg(chr(3).$color.$output[$i]);
      }
    }
  }
  else
  {
    privmsg(chr(3).$color."path not found for $nick");
  }
  return;
}

#####################################################################################################

?>
