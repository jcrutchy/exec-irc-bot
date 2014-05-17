<?php

# gpl2
# by crutchy
# 5-may-2014

# lib.php

define("NICK_EXEC","exec");

#####################################################################################################

function term_echo($msg)
{
  echo "\033[34m$msg\033[0m\n";
}

#####################################################################################################

function privmsg($msg)
{
  echo "IRC_MSG $msg\n";
}

#####################################################################################################

function pm($nick,$msg)
{
  echo "IRC_RAW :".NICK_EXEC." PRIVMSG $nick :$msg\n";
}

#####################################################################################################

function err($msg)
{
  privmsg($msg);
  die();
}

#####################################################################################################

function get_bucket($index)
{
  echo ":".NICK_EXEC." BUCKET_GET :$index\n";
  $f=fopen("php://stdin","r");
  $data="";
  while (True)
  {
    $line=trim(fgets($f));
    if (($line=="") or ($line=="<<EOF>>"))
    {
      break;
    }
    $data=$data.$line;
  }
  if ($data===False)
  {
    err("unable to read bucket data");
  }
  else
  {
    return trim($data);
  }
  fclose($f);
}

#####################################################################################################

function set_bucket($index,$data)
{
  echo ":".NICK_EXEC." BUCKET_SET :$index $data\n";
}

#####################################################################################################

function wget($host,$uri,$port)
{
  $fp=fsockopen($host,$port);
  if ($fp===False)
  {
    term_echo("Error connecting to \"$host\".");
    return;
  }
  fwrite($fp,"GET $uri HTTP/1.0\r\nHost: $host\r\nConnection: Close\r\n\r\n");
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  return $response;
}

#####################################################################################################

?>
