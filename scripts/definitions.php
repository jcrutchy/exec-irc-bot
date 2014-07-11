<?php

# gpl2
# by crutchy
# 12-july-2014

# maybe eventually change to ~query

# http://api.urbandictionary.com/v0/define?term=shitton
# thanks weirdpercent

# https://encyclopediadramatica.es

#####################################################################################################

require_once("lib.php");
$trailing=$argv[1];
$alias=$argv[2];
define("DEFINITIONS_FILE","../data/definitions");
define("DEFINE_SOURCES_FILE","../data/define_sources");
define("MAX_DEF_LENGTH",200);
if (file_exists(DEFINE_SOURCES_FILE)==False)
{
  # if you change this array you need to delete (or amend) the define_sources file in the data path of whatever machine exec is running on
  $sources=array(
    "www.wolframalpha.com"=>array(
      "name"=>"wolframalpha",
      "port"=>80,
      "uri"=>"/input/?i=define%3A%%term%%",
      "template"=>"%%term%%",
      "get_param"=>"",
      "order"=>2,
      "delim_start"=>"context.jsonArray.popups.pod_0200.push( {\"stringified\": \"",
      "delim_end"=>"\",\"mInput\": \"\",\"mOutput\": \"\", \"popLinks\": {} });"),
    "www.urbandictionary.com"=>array(
      "name"=>"urbandictionary",
      "port"=>80,
      "uri"=>"/define.php?term=%%term%%",
      "template"=>"%%term%%",
      "get_param"=>"term",
      "order"=>1,
      "delim_start"=>"<div class='meaning'>",
      "delim_end"=>"</div>"),
    "www.stoacademy.com"=>array(
      "name"=>"stoacademy",
      "port"=>80,
      "uri"=>"/datacore/dictionary.php?searchTerm=%%term%%",
      "template"=>"%%term%%",
      "get_param"=>"",
      "order"=>3,
      "delim_start"=>"<b><u>",
      "delim_end"=>"<p>"));
}
else
{
  $sources=unserialize(file_get_contents(DEFINE_SOURCES_FILE));
}
uasort($sources,"sort_source_order_compare");
$terms=unserialize(file_get_contents(DEFINITIONS_FILE));
switch($alias)
{
  case "~define-count":
    privmsg("custom definition count: ".count($terms));
    break;
  case "~define-sources":
    $out="";
    foreach ($sources as $host => $params)
    {
      if ($out<>"")
      {
        $out=$out.", ";
      }
      $out=$out.$host;
    }
    privmsg("definition sources: $out");
    break;
  case "~define-source-edit":

    break;
  case "~define-source-add":
    # add source using syntax: ~define-source-add $host|$port|$uri|$delim_start|$delim_end|$template|$get_param|$order
    $params=explode("|",$trailing);
    if (count($params)==8)
    {

    }
    else
    {
      privmsg("syntax: ~define-source-add host|port|uri|delim_start|delim_end|template|get_param|order");
      privmsg("example: ~define-source-add www.urbandictionary.com|80|/define.php?term=%%term%%|delim_start|delim_end|template|get_param|order");
    }
    break;
  case "~define-source-delete":

    break;
  case "~define-add":
    $parts=explode(",",$trailing);
    if (count($parts)>1)
    {
      $term=trim($parts[0]);
      array_shift($parts);
      $def=trim(implode(",",$parts));
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
    else
    {
      privmsg("syntax: ~define-add <term>, <definition>");
    }
    break;
  case "~define":
    foreach ($terms as $term => $def)
    {
      $lterms[strtolower($term)]=$term;
    }
    if (isset($lterms[strtolower($trailing)])==True)
    {
      $def=$terms[$lterms[strtolower($trailing)]];
      privmsg("[soylent] $trailing: $def");
    }
    else
    {
      foreach ($sources as $host => $params)
      {
        if (source_define($host,$trailing,$params)==True)
        {
          return;
        }
      }
      privmsg("$trailing: unable to find definition");
    }
    break;
}
file_put_contents(DEFINE_SOURCES_FILE,serialize($sources));

#####################################################################################################

function source_define($host,$term,$params)
{
  $uri=str_replace($params["template"],urlencode($term),$params["uri"]);
  $response=wget($host,$uri,$params["port"]);
  $html=strip_headers($response);
  $i=strpos($html,$params["delim_start"]);
  $def="";
  if ($i!==False)
  {
    $html=substr($html,$i+strlen($params["delim_start"]));
    $i=strpos($html,$params["delim_end"]);
    if ($i!==False)
    {
      $def=trim(strip_tags(substr($html,0,$i)));
      $def=str_replace(array("\n","\r")," ",$def);
      $def=str_replace("  "," ",$def);
      if (strlen($def)>MAX_DEF_LENGTH)
      {
        $def=substr($def,0,MAX_DEF_LENGTH)."...";
      }
    }
  }
  if ($def=="")
  {
    $location=exec_get_header($response,"location");
    if ($location=="")
    {
      return False;
    }
    else
    {
      $new_term=extract_get($location,$params["get_param"]);
      if ($new_term<>$term)
      {
        return source_define($host,$new_term,$params);
      }
      else
      {
        return False;
      }
    }
  }
  else
  {
    privmsg("[".$params["name"]."] ".chr(3)."3$term".chr(3).": ".html_entity_decode($def,ENT_QUOTES,"UTF-8"));
    return True;
  }
}

#####################################################################################################

function sort_source_order_compare($a,$b)
{
  if ($a["order"]==$b["order"])
  {
    return 0;
  }
  elseif ($a["order"]<$b["order"])
  {
    return -1;
  }
  else
  {
    return 1;
  }
}

#####################################################################################################

?>
