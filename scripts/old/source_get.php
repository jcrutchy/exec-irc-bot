<?php

# gpl2
# by crutchy
# 22-june-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];

define("GITHUB_RAW_HOST","raw.githubusercontent.com");

$blacklist=array("`");

/*rmdir
chmod
chown
fopen
exec
passthru
system
popen
file
show_source
readfile
import
dbmopen
open
popen
get
put
pclose
chgrp
chown
__
$_
link
pcntl
apache
posix
proc
preg
show_source
phpinfo
gzinflate
fsockopen
pfsockopen
safe_mode
include
require
ln
cat
parse_perms
dl
shell
cmd
escape
arg
mysql
get_current_user
getmyuid
pconnect
link
ini
leak
syslog
stream
socket
fork
sig
pid
sig
setenv
virtual
upload
delete
edit
write
cmd
rename
mkdir
mv
touch
cp
cd
pico
0x
hex
bin
chr
ord*/

$github_raw_uri="/crutchy-/test/master/";
$check_source=False;
$filename=$trailing;

$parts=explode(" ",$trailing);
if (count($parts)==2)
{
  $check_source=True;
  $github_raw_uri=$parts[0];
  $filename=$parts[1];
}

get_source($filename,False,$check_source);

function get_source($file,$nomsg,$check_source)
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
    $m=0;
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
      if (get_source($line,True,$check_source)==True)
      {
        $n++;
      }
      else
      {
        $m++;
      }
    }
    privmsg("$n files successfully downloaded. $m failed");
    return;
  }
  $fp=fsockopen("ssl://".GITHUB_RAW_HOST,443);
  if ($fp===False)
  {
    $msg="error connecting to \"".GITHUB_RAW_HOST."\"";
    if ($nomsg==False)
    {
      privmsg($msg);
    }
    term_echo($msg);
    return False;
  }
  $uri=$github_raw_uri.$file;
  fwrite($fp,"GET $uri HTTP/1.0\r\nHost: ".GITHUB_RAW_HOST."\r\nConnection: Close\r\n\r\n");
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  $source_file="https://".GITHUB_RAW_HOST.$uri;
  $delim="\r\n\r\n";
  $i=strpos($response,$delim);
  if ($i===False)
  {
    $msg="headers not detected in source file \"$source_file\"";
    if ($nomsg==False)
    {
      privmsg($msg);
    }
    term_echo($msg);
    return False;
  }
  $response=substr($response,$i+strlen($delim));
  if ($response=="")
  {
    $msg="source file \"$source_file\" is empty";
    if ($nomsg==False)
    {
      privmsg($msg);
    }
    term_echo($msg);
    return False;
  }
  if (strtolower(trim($response))=="source file \"$source_file\" not found")
  {
    $msg="source not found";
    if ($nomsg==False)
    {
      privmsg($msg);
    }
    term_echo($msg);
    return False;
  }
  if ($check_source==True)
  {
    # file is from a foreign repository so make sure its not going to be naughty
  }
  $target_file=$target_dir.$file;
  if (file_put_contents($target_file,$response)===False)
  {
    $msg="error writing file \"$target_file\"";
    if ($nomsg==False)
    {
      privmsg($msg);
    }
    term_echo($msg);
    return False;
  }
  else
  {
    $msg="successfully downloaded \"$source_file\" to \"$target_file\"";
    if ($nomsg==False)
    {
      privmsg($msg);
    }
    term_echo($msg);
    return True;
  }
}

#####################################################################################################

?>
