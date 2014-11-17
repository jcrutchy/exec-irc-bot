<?php

# gpl2
# by crutchy

/*
exec:~bucket|5|0|0|1|@|||0|php scripts/bucket.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~var|5|0|0|1|*|||0|php scripts/bucket.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~ls|5|0|0|1|*|||0|php scripts/bucket.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~cd|5|0|0|1|*|||0|php scripts/bucket.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

ini_set("display_errors","on");

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

if ($alias=="~var")
{
  $color="06";
  if ($trailing=="")
  {
    privmsg("syntax: ~var name=value");
    privmsg("if = is omitted, the value of name is returned");
    privmsg("if value is empty, name is deleted");
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
    if ($value=="")
    {
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
    else
    {
      $bucket[$name]=$value;
      privmsg(chr(3).$color."$name = $value");
    }
    set_array_bucket($bucket,"<<USER_VARS>>",True);
  }
  return;
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

  return;
}

#####################################################################################################

if ($trailing=="")
{
  privmsg("GET:   ~bucket <index>");
  privmsg("SET:   ~bucket <index> <data>");
  privmsg("UNSET: ~bucket <index> unset");
  return;
}

$parts=explode(" ",$trailing);

$index=$parts[0];

if (count($parts)==2)
{
  if ($parts[1]=="unset")
  {
    unset_bucket($index);
    if (get_bucket($index)=="")
    {
      privmsg("unset bucket");
    }
    else
    {
      privmsg("error unsetting bucket");
    }
    return;
  }
}

if (count($parts)>=2)
{
  array_shift($parts);
  $data=implode(" ",$parts);
  set_bucket($index,$data);
  if (get_bucket($index)=="")
  {
    privmsg("error setting bucket");
  }
  else
  {
    privmsg("set bucket");
  }
  return;
}

if (count($parts)==1)
{
  $data=get_bucket($index);
  if ($data=="")
  {
    privmsg("bucket not found");
  }
  else
  {
    privmsg($data);
  }
  return;
}

#####################################################################################################

function var_get_path_delim($path)
{
  $delims="$./\\>";
  $delim="";
  for ($i=0;$i<strlen($path);$i++)
  {
    if (strpos($delims,$path[$i])!==False)
    {
      $delim=$path[$i];
      break;
    }
  }
  return $delim;
}

#####################################################################################################

?>
