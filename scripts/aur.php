<?php

#####################################################################################################

/*
exec:~aur|30|0|0|1|||||php scripts/aur.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg(chr(3)."02syntax: ~aur <package_name>");
  return;
}

$uri="/packages/".urlencode($trailing)."/";

$response=wget("aur.archlinux.org",$uri,443);
$html=strip_headers($response);

$description=extract_meta_content($html,"description");

if ($description===False)
{
  privmsg(chr(3)."02error: package not found");
  return;
}

/*$delim1="";
$delim2="";
$description=extract_text($html,$delim1,$delim2);
$description=strip_tags($description);
$description=clean_text($description);*/

if ($description<>"")
{
  privmsg(chr(3)."02".$description);
  privmsg(chr(3)."02https://aur.archlinux.org".$uri);
}

#####################################################################################################

?>
