<?php

# gpl2
# by crutchy
# 1-sep-2014

#####################################################################################################

define("URL_FILE",__DIR__."/sylnt.txt");
define("TEMPLATE_FILE",__DIR__."/sylnt.template");

define("VALID_UPPERCASE","ABCDEFGHIJKLMNOPQRSTUVWXYZ");
define("VALID_LOWERCASE","abcdefghijklmnopqrstuvwxyz");
define("VALID_NUMERIC","0123456789");
define("VALID_ALL",VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC);

define("MIN_LENGTH",3);

$url_blacklist=array("kidd","porn","goat","xxx","sex","fuc");
$body=home();
if (isset($_GET["url"])==True)
{
  # move this to a lib.php function, and add a bot alias script (only accessible from irc)
  $urls=load_urls();
  $long=$_GET["url"];
  if (check_url($long)==True)
  {
    $last="AAA";
    if (count($urls)>0)
    {
      $last=$urls[count($urls)-1];
    }
    $next=get_next($last);
    while (isset($urls[$next])==True)
    {
      $next=get_next($next);
    }
    $line="$next|$long";
    file_put_contents(URL_FILE,$line,FILE_APPEND);
    echo "http://sylnt.us.to/".$next;
    return;
  }
}
elseif (isset($_GET["i"])==True)
{
  $urls=load_urls();
  $short=$_GET["i"];
  if (isset($urls[$short])==True)
  {
    $long=$urls[$short];
    header("Location: $long");
  }
}
$page=file_get_contents(TEMPLATE_FILE);
$page=str_replace("%%body%%",$body,$page);
echo $page;

#####################################################################################################

function home()
{
  $body="<p>welcome to sylnt.us.to</p>\n";
  return $body;
}

#####################################################################################################

function get_next($last)
{
  $next=$last;
  $c=$last[strlen($last)-1];
  if ($c=="9")
  {
    $next=$last."A";
  }
  else
  {
    $next=substr($last,0,strlen($last)-1).VALID_ALL[strpos(VALID_ALL,$c)+1];
  }
  return $next;
}

#####################################################################################################

function load_urls()
{
  $data=file_get_contents(URL_FILE);
  $lines=explode("\n",$data);
  $urls=array();
  for ($i=0;$i<count($lines);$i++)
  {
    $parts=explode("|",$lines[$i]);
    $short=trim($parts[0]);
    array_shift($parts);
    $long=trim(implode("|",$parts));
    if (($short=="") or ($long==""))
    {
      continue;
    }
    $urls[$short]=$long;
  }
  return $urls;
}

#####################################################################################################

function check_url($url)
{
  global $url_blacklist;
  $lower_url=strtolower($url);
  for ($i=0;$i<count($url_blacklist);$i++)
  {
    if (strpos($lower_url,$url_blacklist[$i])!==False)
    {
      return False;
    }
  }
  return True;
}

#####################################################################################################

?>
