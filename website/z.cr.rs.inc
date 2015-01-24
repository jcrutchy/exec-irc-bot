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

define("URL_FILE",__DIR__."../data/z.cr.rs.txt");

#####################################################################################################

$query="";
if (isset($_GET["i"])==True)
{
  $query=trim($_GET["i"]);
}
if ($query<>"")
{
  if (redirect($query)==True)
  {
    die();
  }
  echo "<!DOCTYPE html><html><head><title>z.cr.rs - $query</title><meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\"></head><body><p>Welcome to <b>z.cr.rs</b>.</p><p>-- crutchy</p><p>query: $query</p></body></html>";
}
else
{
  echo "<!DOCTYPE html><html><head><title>z.cr.rs</title><meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\"></head><body><p>Welcome to <b>z.cr.rs</b>.</p><p>-- crutchy</p></body></html>";
}

#####################################################################################################

function redirect($id)
{
  $urls=load_urls();
  var_dump($urls);
  if (isset($urls[$id])==True)
  {
    $long=$urls[$id];
    header("Location: $long");
    return True;
  }
  return False;
}

#####################################################################################################

function load_urls()
{
  echo URL_FILE."\n";
  if (file_exists(URL_FILE)==False)
  {
    return array();
  }
  $data=file_get_contents(URL_FILE);
  if ($data===False)
  {
    return array();
  }
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
  $last="aa";
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
  return "http://sylnt.us.to/$next";
}

#####################################################################################################

function get_next($last)
{
  $next=chr(ord($last[0])+1);
  if ($last[0]=="z")
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
