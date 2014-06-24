<?php

# gpl2
# by crutchy
# 14-june-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];

del_source($trailing);

function del_source($file)
{
  $protected=array("irc.php","exec.txt","scripts","scripts/cmd.php","scripts/lib.php");
  $target_dir="/var/include/vhosts/irciv.us.to/inc/";
  if (in_array($file,$protected)==True)
  {
    privmsg("file \"$file\" is protected and cannot be deleted");
    return;
  }
  $target_file=$target_dir.$file;
  if (file_exists($target_file)==True)
  {
    if (unlink($target_file)==True)
    {
      privmsg("file \"$target_file\" successfully deleted");
    }
    else
    {
      privmsg("error deleting file \"$target_file\"");
    }
  }
  else
  {
    privmsg("file \"$target_file\" not found");
  }
}

#####################################################################################################

?>
