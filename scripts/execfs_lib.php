<?php

# gpl2
# by crutchy

#####################################################################################################

define("BUCKET_FS","<<EXECFS>>");
define("PATH_DELIM","/");

$false=False;

#####################################################################################################

function get_fs()
{
  $fs=get_array_bucket(BUCKET_FS);
  if (isset($fs["modified"])==False)
  {
    $fs["filesystem"][PATH_DELIM]=array();
    $fs["filesystem"][PATH_DELIM]["name"]=PATH_DELIM;
    $fs["filesystem"][PATH_DELIM]["vars"]=array();
    $fs["filesystem"][PATH_DELIM]["permissions"]=array();
    $fs["filesystem"][PATH_DELIM]["children"]=array();
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

function get_path($path)
{
  global $fs;
  global $false;
  $parts=explode(PATH_DELIM,$path);
  array_shift($parts);
  $parent=&$fs["filesystem"][PATH_DELIM];
  for ($i=0;$i<count($parts);$i++)
  {
    $name=trim($parts[$i]);
    if ($name=="")
    {
      return $false;
    }
    if (isset($parent["children"][$name])==False)
    {
      return $false;
    }
    $parent=&$parent["children"][$name];
  }
  return $parent;
}

#####################################################################################################

function get_current_path($nick)
{
  global $fs;
  if (isset($fs["paths"][$nick])==False)
  {
    $fs["paths"][$nick]=PATH_DELIM;
    $fs["modified"]=True;
    return PATH_DELIM;
  }
  return get_path($fs,$fs["paths"][$nick]);
}

#####################################################################################################

function &set_path($path)
{
  global $fs;
  global $false;
  $parts=explode(PATH_DELIM,$path);
  array_shift($parts);
  $parent=&$fs["filesystem"][PATH_DELIM];
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
      $child["children"]=array();
      $parent["children"][$name]=$child;
    }
    $parent=&$parent["children"][$name];
  }
  return $parent;
}

#####################################################################################################

function execfs_get($nick,$name)
{
  global $fs;
  # ~get [%path%]%name%
  
  $path=get_current_path($fs,$nick);

}

#####################################################################################################

function execfs_set($nick,$name,$value)
{
  global $fs;
  global $false;
  # create path as required
  #$path=get_current_path($nick);
  $directory=&set_path("/Level1//Level3");
  if ($directory==$false)
  {
    term_echo("AN ERROR OCCURRED PARSING PATH");
  }
  $directory["vars"][$name]=$value;
  unset($directory);
  #$directory=&set_path("/Level1/Level2/Level3/Level4");
  #$directory["vars"][$name]=$value;
  #unset($directory);
  $fs["filesystem"][PATH_DELIM]["children"]["Level1"]["vars"]="level 1 var";
  var_dump($fs);
}

#####################################################################################################

function execfs_cp()
{
  global $fs;
}

#####################################################################################################

function execfs_mv()
{
  global $fs;
}

#####################################################################################################

function execfs_rm()
{
  global $fs;
}

#####################################################################################################

function execfs_ls()
{
  global $fs;
}

#####################################################################################################

function execfs_cd()
{
  global $fs;
}

#####################################################################################################

?>
