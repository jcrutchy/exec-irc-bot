<?php

# gpl2
# by crutchy
# 23-may-2014

# definitions.php

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");
$msg=$argv[1];
$alias=$argv[2];
define("DEFINITIONS_FILE","../data/definitions");
$terms=unserialize(file_get_contents(DEFINITIONS_FILE));
if ($alias=="~define-add")
{
  $parts=explode(" ",$msg);
  if (count($parts)>1)
  {
    $term=trim($parts[0]);
    array_shift($parts);
    $def=trim(implode(" ",$parts));
    $terms[$term]=$def;
    if (file_put_contents(DEFINITIONS_FILE,serialize($terms))===False)
    {
      privmsg("error writing definitions file");
    }
    else
    {
      privmsg("definition for term \"$term\" set to \"$def\"");
    }
  }
  return;
}
if (isset($terms[$msg])==True)
{
  $def=$terms[$msg];
  privmsg("[soylent] $msg: $def");
}
else
{
  if (urbandictionary($msg)==False)
  {
    if (wolframalpha($msg)==False)
    {
      echo "IRC_MSG $msg: unable to find definition\n";
    }
  }
}

#####################################################################################################

function wolframalpha($msg)
{
  $html=wget("www.wolframalpha.com","/input/?i=define%3A".urlencode($msg),80);
  $delim1="context.jsonArray.popups.pod_0200.push( {\"stringified\": \"";
  $delim2="\",\"mInput\": \"\",\"mOutput\": \"\", \"popLinks\": {} });";
  $i=strpos($html,$delim1)+strlen($delim1);
  $html=substr($html,$i);
  $i=strpos($html,$delim2);
  $def=trim(substr($html,0,$i));
  if (strlen($def)<700)
  {
    if ($def=="")
    {
      return False;
    }
    else
    {
      echo "IRC_MSG [wolframalpha] $msg: $def\n";
      return True;
    }
  }
  else
  { 
    echo "$def\n";
    return False;
  }
}

#####################################################################################################

function urbandictionary($msg)
{
  $html=wget("www.urbandictionary.com","/define.php?term=".urlencode($msg),80);
  $delim1="<meta content='";
  $delim2="' name='Description' property='og:description'>";
  $i=strpos($html,$delim2);
  $html=substr($html,0,$i);
  $def="";
  for ($j=$i;$j>0;$j--)
  {
    if (substr($html,$j,strlen($delim1))==$delim1)
    {
      $def=trim(substr($html,$j+strlen($delim1)));
      break;
    }
  }
  if (strlen($def)<700)
  {
    if ($def=="")
    {
      return False;
    }
    else
    {
      echo "IRC_MSG [urbandictionary] $msg: ".html_entity_decode($def,ENT_QUOTES,"UTF-8")."\n";
      return True;
    }
  }
  else
  { 
    echo "$def\n";
    return False;
  }
}

#####################################################################################################

?>
