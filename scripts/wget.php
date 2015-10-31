<?php

#####################################################################################################

/*
exec:~wget|20|0|0|1|||||php scripts/wget.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);

if ($trailing=="")
{
  privmsg("syntax: ~wget url delim 1 <> delim 2");
  return;
}

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
if (count($parts)<2)
{
  privmsg("syntax: ~wget url delim 1 <> delim 2");
  return;
}
$url=$parts[0];
array_shift($parts);
$trailing=implode(" ",$parts);

$parts=explode("<>",$trailing);
delete_empty_elements($parts);
if (count($parts)<2)
{
  privmsg("syntax: ~wget url delim 1 <> delim 2");
  return;
}

$delim1=trim($parts[0]);
$delim2=trim($parts[1]);

$host="";
$uri="";
$port="";

if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  return;
}

$response=wget_ssl($host,$uri,$port);

$result=extract_text($response,$delim1,$delim2);

if ($result===False)
{
  return;
}

$result=strip_tags($result);

$result=html_decode($result);
$result=html_decode($result);

$result=trim($result);

if ($result=="")
{
  return;
}

if (strlen($result)>300)
{
  $result=trim(substr($result,0,300))."...";
}

privmsg(chr(3)."07".$result);

#####################################################################################################

?>
