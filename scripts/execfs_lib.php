<?php

# gpl2
# by crutchy

#####################################################################################################

define("BUCKET_FS","<<EXECFS>>");
define("PATH_DELIM","/");

#####################################################################################################

function get_fs()
{
  $fs=get_array_bucket(BUCKET_FS);
  if (isset($fs["modified"])==False)
  {
    $fs["filesystem"][PATH_DELIM]=array();
    $fs["filesystem"][PATH_DELIM]["vars"]=array();
    $fs["filesystem"][PATH_DELIM]["permissions"]=array();
    $fs["filesystem"][PATH_DELIM]["parent"]=False;
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
  $parts=explode(PATH_DELIM,$path);
  array_shift($parts);
  $result=$fs["filesystem"][PATH_DELIM];
  for ($i=0;$i<count($parts);$i++)
  {
    $child=$parts[$i];
    if ($child=="")
    {
      return False;
    }
    if (isset($result["children"][$child])==True)
    {
      $result=$result["children"][$child];
    }
    else
    {
      privmsg("error: path not found");
      return False;
    }
  }
  return $result;
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

function set_path($path)
{
  global $fs;
  $parts=explode(PATH_DELIM,$path);
  array_shift($parts);
  return create_child($fs["filesystem"][PATH_DELIM],$parts);
}

#####################################################################################################

function create_child(&$parent,$parts)
{
  global $fs;
  $name=$parts[0];
  array_shift($parts);
  $child=array();
  $child["vars"]=array();
  $child["permissions"]=array();
  $child["parent"]=&$parent;
  $child["children"]=array();
  $parent["children"][$name]=&$child;
  if (count($parts)>0)
  {
    return create_child($child,$parts);
  }
  return $child;
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
  # create path as required
  #$path=get_current_path($nick);
  $directory=set_path($name);
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
