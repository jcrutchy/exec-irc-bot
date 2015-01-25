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

define("PASTES_FILE",__DIR__."/../../data/pastemyto_pastes.txt");
define("HOME_FILE",__DIR__."/pastemyto_home.txt");

#####################################################################################################

if (file_exists(PASTES_FILE)==False)
{
  die("pastes file not found");
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
  $pastes=load_pastes();
  if (isset($pastes[$query])==True)
  {
    die($pastes[$query]);
  }
  die("unknown query: $query");
}
elseif (isset($_POST["content"])==True)
{
  $content=trim($_POST["content"]);
  if ($content<>"")
  {
    save_paste($content);
  }
}
echo file_get_contents(HOME_FILE);

#####################################################################################################

function load_pastes()
{
  $data=file_get_contents(PASTES_FILE);
  $lines=explode("\n",$data);
  $pastes=array();
  for ($i=0;$i<count($lines);$i++)
  {
    $line=trim($lines[$i]);
    if ($line=="")
    {
      continue;
    }
    $parts=explode("|",$line);
    $id=$parts[0];
    $content=unserialize(base64_decode($parts[1]));
    if (($id=="") or ($content==""))
    {
      continue;
    }
    $pastes[$id]=$content;
  }
  return $pastes;
}

#####################################################################################################

function save_paste($content)
{
  $pastes=load_pastes();
  $last="zz";
  $n=count($pastes);
  if ($n>0)
  {
    $keys=array_keys($pastes);
    $last=$keys[$n-1];
  }
  $next=get_next($last);
  $pastes[$next]=$content;
  $data="";
  foreach ($pastes as $id => $content)
  {
    $data=$data."$id|".base64_encode(serialize($content))."\n";
  }
  file_put_contents(PASTES_FILE,$data);
  header("Location: http://paste.my.to/$next");
  die();
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
