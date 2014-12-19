<?php

# gpl2
# by crutchy

#####################################################################################################

define("BUCKET_FS","<<EXECFS>>");
define("PATH_DELIM","/");

$false=False;

#####################################################################################################

function execfs_tests()
{
  global $privmsg;
  $privmsg="";
}

#####################################################################################################

function get_fs()
{
  global $false;
  $fs=get_array_bucket(BUCKET_FS);
  if (isset($fs["modified"])==False)
  {
    $fs["filesystem"][PATH_DELIM]=array();
    $fs["filesystem"][PATH_DELIM]["name"]=PATH_DELIM;
    $fs["filesystem"][PATH_DELIM]["vars"]=array();
    $fs["filesystem"][PATH_DELIM]["permissions"]=array();
    $fs["filesystem"][PATH_DELIM]["children"]=array();
    $fs["filesystem"][PATH_DELIM]["parent"]=&$false;
    $fs["paths"]=array();
    $fs["modified"]=True;
  }
  return $fs;
}

#####################################################################################################

function set_fs()
{
  global $fs;
  if ($fs["modified"]==False)
  {
    return;
  }
  $fs["modified"]=False;
  set_array_bucket($fs,BUCKET_FS,True);
}

#####################################################################################################

function get_path(&$directory)
{
  global $false;
  $result=$directory["name"];
  $parent=$directory["parent"];
  while ($parent<>$false)
  {
    if ($parent["name"]<>PATH_DELIM)
    {
      $result=PATH_DELIM.$result;
    }
    $result=$parent["name"].$result;
    $parent=$parent["parent"];
  }
  return $result;
}

#####################################################################################################

function &get_directory($path)
{
  global $fs;
  global $false;
  term_echo("*** get_directory: $path");
  $parent=&$fs["filesystem"][PATH_DELIM];
  if ($path<>PATH_DELIM)
  {
    $parts=explode(PATH_DELIM,$path);
    array_shift($parts);
    for ($i=0;$i<count($parts);$i++)
    {
      $name=trim($parts[$i]);
      if ($name=="")
      {
        term_echo("*** get_directory: name = \"\"");
        return $false;
      }
      if ($name=="..")
      {
        if ($parent["parent"]<>$false)
        {
          $parent=&$parent["parent"];
        }
        continue;
      }
      if (isset($parent["children"][$name])==False)
      {
        term_echo("*** get_directory: child not found");
        return $false;
      }
      $parent=&$parent["children"][$name];
    }
  }
  return $parent;
}

#####################################################################################################

function directory_fix(&$directory) # use this function to update existing directory structures
{
  global $fs;
  if (isset($directory["data"])==False)
  {
    $directory["data"]=array();
    $fs["modified"]=True;
  }
}

#####################################################################################################

function &get_current_directory($nick)
{
  global $fs;
  global $false;
  if (isset($fs["paths"][$nick])==False)
  {
    $fs["paths"][$nick]=PATH_DELIM;
    $fs["modified"]=True;
    term_echo("*** get_current_directory: path for $nick not found");
    return $fs["filesystem"][PATH_DELIM];
  }
  term_echo("*** get_current_directory: path for $nick found => ".$fs["paths"][$nick]);
  $directory=&get_directory($fs["paths"][$nick]);
  if ($directory==$false)
  {
    term_echo("*** get_current_directory: directory == false");
    return $false;
  }
  directory_fix($directory);
  return $directory;
}

#####################################################################################################

function &set_directory($path)
{
  global $fs;
  global $false;
  term_echo("*** set_directory: $path");
  $parts=explode(PATH_DELIM,$path);
  array_shift($parts);
  $parent=&$fs["filesystem"][PATH_DELIM];
  if (count($parts)>0)
  {
    for ($i=0;$i<count($parts);$i++)
    {
      $name=trim($parts[$i]);
      if ($name=="")
      {
        return $false;
      }
      if (isset($parent["children"][$name])==False)
      {
        $child=array();
        $child["name"]=$name;
        $child["vars"]=array();
        $child["permissions"]=array();
        $child["data"]=array();
        $child["children"]=array();
        $parent["children"][$name]=$child;
        $parent["children"][$name]["parent"]=&$parent;
      }
      $parent=&$parent["children"][$name];
    }
  }
  $fs["modified"]=True;
  return $parent;
}

#####################################################################################################

function execfs_privmsg($msg)
{
  global $privmsg;
  if ($privmsg===True)
  {
    privmsg(chr(3)."13".$msg);
  }
  else
  {
    $privmsg=$msg;
  }
}

#####################################################################################################

function get_absolute_path($nick,$path)
{
  global $fs;
  global $false;
  $directory=&get_current_directory($nick);
  if ($directory==$false)
  {
    term_echo("*** get_relative_directory: get_current_directory returned false");
    return $false;
  }
  if (substr($path,strlen($path)-1)==PATH_DELIM)
  {
    $path=substr($path,0,strlen($path)-1);
  }
  if ($path=="")
  {
    $path=PATH_DELIM;
  }
  $dirpath=get_path($directory);
  if (substr($path,0,1)<>PATH_DELIM)
  {
    if ($dirpath<>PATH_DELIM)
    {
      $path=$dirpath.PATH_DELIM.$path;
    }
    else
    {
      $path=PATH_DELIM.$path;
    }
  }
  unset($directory);
  return $path;
}

#####################################################################################################

function execfs_get($nick,$name)
{
  global $fs;
  global $false;
  $path="";
  $parts=explode(PATH_DELIM,$name);
  if (count($parts)>1)
  {
    $name=array_pop($parts);
    $path=trim(implode(PATH_DELIM,$parts));
  }
  $path=get_absolute_path($nick,$path);
  $directory=&get_directory($path);
  if ($directory==$false)
  {
    execfs_privmsg("error: invalid path");
    return;
  }
  if (isset($directory["vars"][$name])==True)
  {
    execfs_privmsg($name." = ".$directory["vars"][$name]);
  }
  else
  {
    privmsg("error: var \"$name\" not found in path \"".get_path($directory)."\"");
  }
  unset($directory);
}

#####################################################################################################

function execfs_set($nick,$name,$value)
{
  global $fs;
  global $false;
  $path="";
  $parts=explode(PATH_DELIM,$name);
  if (count($parts)>1)
  {
    $name=array_pop($parts);
    $path=trim(implode(PATH_DELIM,$parts));
  }
  $path=get_absolute_path($nick,$path);
  $directory=&set_directory($path);
  if ($directory==$false)
  {
    execfs_privmsg("error: invalid path");
    return;
  }
  $directory["vars"][$name]=$value;
  $dirpath=get_path($directory);
  execfs_privmsg("var \"$name\" set to \"$value\" in path \"$dirpath\"");
  unset($directory);
  $fs["modified"]=True;
}

#####################################################################################################

function execfs_unset($nick,$name)
{
  global $fs;
  global $false;
  $path="";
  $parts=explode(PATH_DELIM,$name);
  if (count($parts)>1)
  {
    $name=array_pop($parts);
    $path=trim(implode(PATH_DELIM,$parts));
  }
  $path=get_absolute_path($nick,$path);
  $directory=&get_directory($path);
  if ($directory==$false)
  {
    execfs_privmsg("error: invalid path");
    return;
  }
  $dirpath=get_path($directory);
  if (isset($directory["vars"][$name])==True)
  {
    unset($directory["vars"][$name]);
    $fs["modified"]=True;
    execfs_privmsg("var \"$name\" successfully deleted from \"$dirpath\"");
  }
  else
  {
    execfs_privmsg("error: var \"$name\" not found in \"$dirpath\"");
  }
  unset($directory);
}

#####################################################################################################

function execfs_rd($nick,$name)
{
  # TODO
}

#####################################################################################################

function execfs_ls($nick,$path)
{
  global $fs;
  global $false;
  $path=get_absolute_path($nick,$path);
  $directory=&get_directory($path);
  if ($directory==$false)
  {
    execfs_privmsg("error: invalid path");
    return;
  }
  $children=array_keys($directory["children"]);
  $vars=array_keys($directory["vars"]);
  $path=get_path($directory);
  execfs_privmsg("current path for $nick: $path");
  if (count($children)>0)
  {
    execfs_privmsg("children: ".implode(" ",$children));
  }
  if (count($vars)>0)
  {
    execfs_privmsg("vars: ".implode(" ",$vars));
  }
  unset($directory);
}

#####################################################################################################

function execfs_cd($nick,$path)
{
  global $fs;
  global $false;
  if (substr($path,0,1)<>PATH_DELIM)
  {
    $directory=&get_current_directory($nick);
    if ($directory==$false)
    {
      execfs_privmsg("error: invalid current path for $nick");
      return;
    }
    $dirpath=get_path($directory);
    if ($dirpath<>PATH_DELIM)
    {
      $path=$dirpath.PATH_DELIM.$path;
    }
    else
    {
      $path=PATH_DELIM.$path;
    }
    term_echo("*** execfs_cd: path=$path");
  }
  if (substr($path,strlen($path)-1)==PATH_DELIM)
  {
    $path=substr($path,0,strlen($path)-1);
  }
  $directory=&get_directory($path);
  if ($directory==$false)
  {
    execfs_privmsg("error: path not found");
    return;
  }
  $fs["paths"][$nick]=get_path($directory);
  unset($directory);
  $fs["modified"]=True;
  execfs_privmsg("current path for $nick changed to \"".$fs["paths"][$nick]."\"");
}

#####################################################################################################

function execfs_md($nick,$path)
{
  global $fs;
  global $false;
  $directory=&get_current_directory($nick);
  if ($directory==$false)
  {
    execfs_privmsg("error: invalid current path for $nick");
    return;
  }
  $parts=explode(PATH_DELIM,$path);
  $dirpath=get_path($directory);
  if (substr($path,0,1)<>PATH_DELIM)
  {
    if ($dirpath<>PATH_DELIM)
    {
      $path=$dirpath.PATH_DELIM.$path;
    }
    else
    {
      $path=PATH_DELIM.$path;
    }
  }
  unset($directory);
  $directory=&set_directory($path);
  if ($directory==$false)
  {
    execfs_privmsg("error: invalid path");
    return;
  }
  $dirpath=get_path($directory);
  execfs_privmsg("path \"$dirpath\" created");
  unset($directory);
  $fs["modified"]=True;
}

#####################################################################################################

?>
