<?php

# gpl2
# by crutchy

#####################################################################################################

define("BUCKET_EXECFS_VARS","<<EXECFS_VARS>>");
define("BUCKET_EXECFS_PATHS","<<EXECFS_PATHS>>");
define("BUCKET_EXECFS_PERMISSIONS","<<EXECFS_PERMISSIONS>>");

#####################################################################################################

function execfs_get_path_delim($path)
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

function execfs_rm($name,$nick,&$msg)
{
  $name=trim($name);
  $bucket=get_array_bucket(BUCKET_EXECFS_VARS);
  $paths=get_array_bucket(BUCKET_EXECFS_PATHS);
  if (isset($bucket[$name])==False)
  {
    if (isset($paths[$nick])==True)
    {
      $name=$paths[$nick].$name;
    }
  }
  if (isset($bucket[$name])==False)
  {
    $msg="$name not found";
    return False;
  }
  else
  {
    unset($bucket[$name]);
    $msg="$name deleted";
    set_array_bucket($bucket,BUCKET_EXECFS_VARS,True);
    return True;
  }
}

#####################################################################################################

?>
