<?php

#####################################################################################################

/*
  RewriteEngine On
  RewriteRule ^/(.*)$ /index.php?i=$1 [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
*/

#####################################################################################################

define("URL_FILE",__DIR__."/../../data/zcrrs_urls.txt");
define("HOME_FILE",__DIR__."/zcrrs_home.txt");

#####################################################################################################

if (file_exists(URL_FILE)==False)
{
  die("url file not found");
}
if (file_exists(HOME_FILE)==False)
{
  die("home file not found");
}

$query="";
if (isset($_GET["i"])==True)
{
  $query=trim($_GET["i"]);
}
if ($query<>"")
{
  redirect($query);
  die("unknown query: $query");
}
elseif (isset($_POST["url"])==True)
{
  $url=trim($_POST["url"]);
  if ($url<>"")
  {
    die(shorten($url));
  }
}
echo file_get_contents(HOME_FILE);

#####################################################################################################

function redirect($id)
{
  $urls=load_urls();
  if (isset($urls[$id])==True)
  {
    $long=$urls[$id];
    header("Location: $long");
    die();
  }
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

function shorten($url)
{
  $urls=load_urls();
  $last="zz";
  $n=count($urls);
  if ($n>0)
  {
    $keys=array_keys($urls);
    $last=$keys[$n-1];
  }
  $next=get_next($last);
  $urls[$next]=$url;
  $data="";
  foreach ($urls as $id => $long)
  {
    $data=$data."$id|$long\n";
  }
  file_put_contents(URL_FILE,$data);
  return "http://z.cr.rs/$next";
}

#####################################################################################################

function get_next($last)
{
  if ($last[1]=="z")
  {
    $next=chr(ord($last[0])+1);
  }
  else
  {
    $next=$last[0];
  }
  if (ord($next)>ord("z"))
  {
    $next="a";
  }
  if ($last[1]=="z")
  {
    $next=$next."a";
  }
  else
  {
    $next=$next.chr(ord($last[1])+1);
  }
  return $next;
}

#####################################################################################################

?>
