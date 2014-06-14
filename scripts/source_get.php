<?php

# gpl2
# by crutchy
# 14-june-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];

define("GITHUB_RAW_HOST","raw.githubusercontent.com");
define("GITHUB_RAW_URI","/crutchy-/test/master/");

get_source($trailing);

function get_source($file,$nomsg=False)
{
  $target_dir="/var/include/vhosts/irciv.us.to/inc/";
  if ($file=="*")
  {
    $fn=$target_dir."scripts/filelist.txt";
    if (file_exists($fn)==False)
    {
      privmsg("file \"$fn\" not found");
      return;
    }
    $data=file_get_contents($fn);
    if ($data===False)
    {
      privmsg("error reading file \"$fn\"");
      return;
    }
    $lines=explode("\n",$data);
    $n=0;
    for ($i=0;$i<count($lines);$i++)
    {
      $line=trim($lines[$i]);
      if ($line=="")
      {
        continue;
      }
      if (substr($line,0,1)=="#")
      {
        continue;
      }
      if (get_source($line,True)==True)
      {
        $n++;
      }
    }
    privmsg("$n files downloaded");
    return;
  }
  $fp=fsockopen("ssl://".GITHUB_RAW_HOST,443);
  if ($fp===False)
  {
    if ($nomsg==False)
    {
      privmsg("error connecting to \"".GITHUB_RAW_HOST."\"");
    }
    return False;
  }
  $uri=GITHUB_RAW_URI.$file;
  fwrite($fp,"GET $uri HTTP/1.0\r\nHost: ".GITHUB_RAW_HOST."\r\nConnection: Close\r\n\r\n");
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  $delim="\r\n\r\n";
  $i=strpos($response,$delim);
  if ($i===False)
  {
    if ($nomsg==False)
    {
      privmsg("headers not detected");
    }
    return False;
  }
  $response=substr($response,$i+strlen($delim));
  if ($response=="")
  {
    if ($nomsg==False)
    {
      privmsg("source is empty");
    }
    return False;
  }
  $source_file="https://".GITHUB_RAW_HOST.$uri;
  if (strtolower(trim($response))=="not found")
  {
    if ($nomsg==False)
    {
      privmsg("source not found");
    }
    return False;
  }
  $target_file=$target_dir.$file;
  if (file_put_contents($target_file,$response)===False)
  {
    if ($nomsg==False)
    {
      privmsg("error writing file \"$target_file\"");
    }
    return False;
  }
  else
  {
    if ($nomsg==False)
    {
      privmsg("successfully downloaded \"$source_file\" to \"$target_file\"");
    }
    return True;
  }
}

#####################################################################################################

?>
