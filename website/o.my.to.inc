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

define("URL_FILE",__DIR__."/../../data/omyto_urls.txt");
define("HOME_FILE",__DIR__."/omyto_home.txt");

define("VALID_UPPERCASE","ABCDEFGHIJKLMNOPQRSTUVWXYZ");
define("VALID_LOWERCASE","abcdefghijklmnopqrstuvwxyz");
define("VALID_NUMERIC","0123456789");

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
  $url=trim(substr($_POST["url"],0,1024));
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
    $line=trim($lines[$i]);
    if ($line=="")
    {
      continue;
    }
    $parts=explode("|",$line);
    $id=$parts[0];
    array_shift($parts);
    $long=trim(implode("|",$parts));
    if (($id=="") or ($long==""))
    {
      continue;
    }
    $urls[$id]=$long;
  }
  return $urls;
}

#####################################################################################################

function shorten($url)
{
  $urls=load_urls();
  if (isset($_POST["mode"])==True)
  {
    if (strtolower(trim($_POST["mode"]))=="title")
    {
      $next=get_title($url);
      if ($next!==False)
      {
        $urls[$next]=$url;
        save_urls($urls);
        output_link($next);
        return;
      }
    }
  }
  $last="zz";
  $n=count($urls);
  if ($n>0)
  {
    $keys=array_keys($urls);
    $last=$keys[$n-1];
  }
  $next=get_next($last);
  $urls[$next]=$url;
  save_urls($urls);
  output_link($next);
}

#####################################################################################################

function output_link($id)
{
  $url="http://o.my.to/$id";
  #echo "<html><head><title>o.my.to</title></head><body><a href=\"$url\">$url</a></body></html>";
  echo $url;
}

#####################################################################################################

function save_urls(&$urls)
{
  $data="";
  foreach ($urls as $id => $long)
  {
    $data=$data."$id|$long\n";
  }
  file_put_contents(URL_FILE,$data);
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

function get_title($url)
{
  require_once("/var/include/vhosts/irciv.bot.nu/inc/scripts/lib_http.php");
  $url=get_redirected_url($url);
  $host="";
  $uri="";
  $port=80;
  if (get_host_and_uri($url,$host,$uri,$port)==False)
  {
    return False;
  }
  $breakcode="return ((strpos(strtolower(\$response),\"</title>\")!==False) or (strlen(\$response)>=10000));";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,"",20,$breakcode,256);
  $html=strip_headers($response);
  $title=extract_raw_tag($html,"title");
  $title=html_decode($title);
  $title=html_decode($title);
  $title=strtolower(filter($title,VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC."— "));
  $title=str_replace(" ","_",$title);
  $title=str_replace("—","_",$title);
  while (strpos($title,"__")!==False)
  {
    $title=str_replace("__","_",$title);
  }
  if ($title=="")
  {
    return False;
  }
  return $title;
}

#####################################################################################################

function filter($value,$valid_chars)
{
  $result="";
  for ($i=0;$i<strlen($value);$i++)
  {
    if (strpos($valid_chars,$value[$i])!==False)
    {
      $result=$result.$value[$i];
    }
  }
  return $result;
}

#####################################################################################################

?>
