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
    $fs["filesystem"][PATH_DELIM]["children"]=array();
    $fs["filesystem"][PATH_DELIM]["vars"]=array();
    $fs["permissions"][PATH_DELIM]=array();
    $fs["permissions"][PATH_DELIM]["children"]=array();
    $fs["permissions"][PATH_DELIM]["vars"]=array();
    $fs["paths"]=array();
    $fs["modified"]=True;
  }
  return $fs;
}

#####################################################################################################

function set_fs(&$fs)
{
  if ($fs["modified"]==False)
  {
    return;
  }
  $fs["modified"]=False;
  set_array_bucket($fs,BUCKET_FS,True);
}

#####################################################################################################

function get_path(&$fs,$path)
{
  # /path/to/my/var
  if (isset($fs["paths"][$nick])==False)
  {
    $fs["paths"][$nick]=PATH_DELIM;
    $fs["modified"]=True;
    return PATH_DELIM;
  }
  $parts=explode(PATH_DELIM,$fs["paths"][$nick]);
  array_shift($parts);
  $result=&$fs["filesystem"][PATH_DELIM];
  for ($i=0;$i<count($parts);$i++)
  {
    $child=$parts[$i];
    if ($child=="")
    {
      return False;
    }
    if (isset($result["children"][$child])==True)
    {

    }
    else
    {
      privmsg("error: path not found");
      return False;
    }
  }
}

#####################################################################################################

function get_current_path(&$fs,$nick)
{
  if (isset($fs["paths"][$nick])==False)
  {
    $fs["paths"][$nick]=PATH_DELIM;
    $fs["modified"]=True;
    return PATH_DELIM;
  }
  return get_path($fs,$fs["paths"][$nick]);
}

#####################################################################################################

function execfs_get(&$fs,$nick,$name)
{
  # ~get [%path%]%name%
  
  $path=get_current_path($fs,$nick);

}

#####################################################################################################

function execfs_set(&$fs,$nick,$name,$value)
{

}

#####################################################################################################

function execfs_cp(&$fs)
{

}

#####################################################################################################

function execfs_mv(&$fs)
{

}

#####################################################################################################

function execfs_rm(&$fs)
{

}

#####################################################################################################

function execfs_ls(&$fs)
{

}

#####################################################################################################

function execfs_cd(&$fs)
{

}

#####################################################################################################

?>
