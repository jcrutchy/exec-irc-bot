<?php

#####################################################################################################

/*
exec:~define|60|0|0|1|||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-list|60|0|0|1|crutchy||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-add|10|0|0|0|||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-delete|10|0|0|0|||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-count|5|0|0|1|||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-sources|5|0|0|1|||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-source-edit|5|0|0|1|crutchy||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-source-param|5|0|0|1|crutchy||||php scripts/definitions.php %%trailing%% %%alias%%
exec:~define-source-delete|5|0|0|1|crutchy||||php scripts/definitions.php %%trailing%% %%alias%%
*/

#####################################################################################################

# maybe eventually change to ~query

# http://api.urbandictionary.com/v0/define?term=shitton
# thanks weirdpercent

# https://encyclopediadramatica.es
# freeonlinedictionary
# wiktionary
# google
# http://en.memory-alpha.org

# change data file format to lines instead of serialized string

#####################################################################################################

require_once("lib.php");
$trailing=$argv[1];
$alias=$argv[2];
#$debug=get_bucket("<<DEFINE_DEBUG>>");
$debug="";
define("DEFINITIONS_FILE","../data/definitions");
define("DEFINE_SOURCES_FILE","../data/define_sources");
define("MAX_DEF_LENGTH",200);
$sources=array(
  "en.wikipedia.org"=>array(
    "name"=>"wikipedia",
    "port"=>80,
    "uri"=>"/wiki/%%term%%",
    "template"=>"%%term%%",
    "get_param"=>"wiki/",
    "order"=>2,
    "delim_start"=>"<p>",
    "delim_end"=>"</p>",
    "ignore"=>"Other reasons this message may be displayed:",
    "space_delim"=>"_"),
  "www.urbandictionary.com"=>array(
    "name"=>"urbandictionary",
    "port"=>80,
    "uri"=>"/define.php?term=%%term%%",
    "template"=>"%%term%%",
    "get_param"=>"term=",
    "order"=>1,
    "delim_start"=>"<div class='meaning'>",
    "delim_end"=>"</div>",
    "ignore"=>"",
    "space_delim"=>""));
if (file_exists(DEFINE_SOURCES_FILE)==False)
{
  term_echo("*** DEFINITIONS ERROR: DEFINE SOURCES FILE NOT FOUND");
}
else
{
  $data=file_get_contents(DEFINE_SOURCES_FILE);
  if ($data===False)
  {
    term_echo("*** DEFINITIONS ERROR: UNABLE TO READ DEFINE SOURCES FILE CONTENTS");
  }
  else
  {
    $data=unserialize($data);
    if ($data===False)
    {
      term_echo("*** DEFINITIONS ERROR: DEFINE SOURCES FILE CONTAINS INVALID SERIALIZED ARRAY DATA");
    }
    else
    {
      $sources=$data;
    }
  }
}
reorder($sources);
$terms=array();
if (file_exists(DEFINITIONS_FILE)==False)
{
  term_echo("*** DEFINITIONS ERROR: DEFINITIONS FILE NOT FOUND");
}
else
{
  $data=file_get_contents(DEFINITIONS_FILE);
  if ($data===False)
  {
    term_echo("*** DEFINITIONS ERROR: UNABLE TO READ DEFINITIONS FILE CONTENTS");
  }
  else
  {
    $data=unserialize($data);
    if ($data===False)
    {
      term_echo("*** DEFINITIONS ERROR: DEFINITIONS FILE CONTAINS INVALID SERIALIZED ARRAY DATA");
    }
    else
    {
      $terms=$data;
    }
  }
}
switch($alias)
{
  case "~define-count":
    privmsg("custom definition count: ".count($terms));
    break;
  case "~define-sources":
    /*$out="";
    foreach ($sources as $host => $params)
    {
      if ($out<>"")
      {
        $out=$out.", ";
      }
      $out=$out.$host;
    }
    privmsg("definition sources: $out");*/
    foreach ($sources as $host => $params)
    {
      privmsg("$host => ".$params["name"]."|".$params["port"]."|".$params["uri"]."|".$params["template"]."|".$params["get_param"]."|".$params["order"]."|".$params["delim_start"]."|".$params["delim_end"]."|".$params["ignore"]."|".$params["space_delim"]);
      usleep(0.5*1e6);
    }
    break;
  case "~define-source-edit":
    if ($trailing=="debug on")
    {
      set_bucket("<<DEFINE_DEBUG>>","ON");
      privmsg("define: debug mode enabled");
      return;
    }
    if ($trailing=="debug off")
    {
      unset_bucket("<<DEFINE_DEBUG>>");
      privmsg("define: debug mode disabled");
      return;
    }
    $params=explode("|",$trailing);
    if (count($params)==11)
    {
      $host=trim($params[0]);
      $action="inserted";
      if (isset($sources[$host])==True)
      {
        $action="updated";
      }
      $sources[$host]["name"]=trim($params[1]);
      $sources[$host]["port"]=trim($params[2]);
      $sources[$host]["uri"]=trim($params[3]);
      $sources[$host]["template"]=trim($params[4]);
      $sources[$host]["get_param"]=trim($params[5]);
      $sources[$host]["order"]=trim($params[6]);
      $sources[$host]["delim_start"]=trim($params[7]);
      $sources[$host]["delim_end"]=trim($params[8]);
      $sources[$host]["ignore"]=trim($params[9]);
      $sources[$host]["space_delim"]=trim($params[9]);
      reorder($sources);
      privmsg("source \"$host\" $action");
    }
    else
    {
      privmsg("syntax: ~define-source-edit host|name|port|uri|template|get_param|order|delim_start|delim_end|ignore|space_delim");
      privmsg("example: ~define-source-edit www.urbandictionary.com|urbandictionary|80|/define.php?term=%%term%%|%%term%%|term|1|<div class='meaning'>|</div>||");
    }
    break;
  case "~define-source-param":
    $params=explode(" ",$trailing);
    if (count($params)>=3)
    {
      $host=trim($params[0]);
      $param=trim($params[1]);
      array_shift($params);
      array_shift($params);
      $value=trim(implode(" ",$params));
      if (isset($sources[$host])==True)
      {
        $action="inserted";
        if (isset($sources[$host])==True)
        {
          $action="updated";
        }
        $sources[$host][$param]=$value;
        $suffix="";
        if ($param=="order")
        {
          reorder($sources);
          $suffix=" (after reoder)";
        }
        privmsg("param \"$param\" for source \"$host\" $action with \"".$sources[$host][$param]."\"$suffix");
      }
      else
      {
        privmsg("source \"$host\" not found");
      }
    }
    else
    {
      privmsg("syntax: ~define-source-param host param value");
    }
    break;
  case "~define-source-delete":
    if (isset($sources[$trailing])==True)
    {
      unset($sources[$trailing]);
      reorder($sources);
      privmsg("source \"$trailing\" deleted");
    }
    else
    {
      privmsg("source \"$trailing\" not found");
    }
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
  case "~define-delete":
    $lterms=array();
    foreach ($terms as $term => $def)
    {
      $lterms[strtolower($term)]=$term;
    }
    term_echo("DEFINE-DELETE: TRAILING = $trailing");
    if (isset($lterms[strtolower($trailing)])==True)
    {
      unset($terms[$lterms[strtolower($trailing)]]);
      if (file_put_contents(DEFINITIONS_FILE,serialize($terms))===False)
      {
        privmsg("error writing definitions file");
      }
      else
      {
        privmsg("term \"$term\" deleted from local definitions file");
      }
    }
    else
    {
      privmsg("syntax: ~define-delete <term>");
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
      privmsg("[local] $trailing: $def");
    }
    else
    {
      foreach ($sources as $host => $params)
      {
        if (source_define($host,$trailing,$params)==True)
        {
          return;
        }
        else
        {
          term_echo("$trailing: unable to find definition @ $host");
        }
      }
      privmsg("$trailing: unable to find definition");
    }
    break;
  case "~define-list":
    foreach ($terms as $term => $def)
    {
      term_echo("[$term] => $def");
    }
    privmsg("definitions added in irc has been output to terminal");
    break;
}
file_put_contents(DEFINE_SOURCES_FILE,serialize($sources));

#####################################################################################################

function source_define($host,$term,$params)
{
  global $debug;
  $sterm=$term;
  if ($params["space_delim"]<>"")
  {
    $sterm=str_replace(" ",$params["space_delim"],$sterm);
  }
  $uri=str_replace($params["template"],urlencode($sterm),$params["uri"]);
  term_echo("*** DEFINE: trying $host$uri on port ".$params["port"]);
  $response=wget($host,$uri,$params["port"],ICEWEASEL_UA,"",20);
  $html=strip_headers($response);
  $html=replace_ctrl_chars($html," ");
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  if ($debug=="ON")
  {
    privmsg("debug [$host]: uri = \"$uri\"");
    $L=strlen($html);
    privmsg("debug [$host]: html length = \"$L\"");
    unset($L);
    privmsg("debug [$host]: delim_start = \"".$params["delim_start"]."\"");
    privmsg("debug [$host]: delim_end = ".$params["delim_end"]."\"");
  }
  $i=strpos($html,$params["delim_start"]);
  $def="";
  if ($i!==False)
  {
    if ($debug=="ON")
    {
      privmsg("debug [$host]: delim_start pos = \"$i\"");
    }
    $html=substr($html,$i+strlen($params["delim_start"]));
    $i=strpos($html,$params["delim_end"]);
    if ($i!==False)
    {
      if ($debug=="ON")
      {
        privmsg("debug [$host]: delim_end pos = \"$i\"");
      }
      $def=trim(strip_tags(substr($html,0,$i)));
      $def=str_replace(array("\n","\r")," ",$def);
      $def=str_replace("  "," ",$def);
      if (strlen($def)>MAX_DEF_LENGTH)
      {
        $def=trim(substr($def,0,MAX_DEF_LENGTH))."...";
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
      $new_term=extract_text($location,$params["get_param"],"&",True);
      if ($new_term<>$term)
      {
        term_echo("redirecting to \"$location\"");
        if ($debug=="ON")
        {
          privmsg("debug [$host]: redirecting to \"$location\"");
        }
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
    if (($params["ignore"]<>"") and (strpos($def,$params["ignore"])!==False))
    {
      return False;
    }
    if (strpos($def,"There aren't any definitions")!==False)
    {
      return False;
    }
    privmsg("[".$params["name"]."] ".chr(3)."03$term".chr(3).": ".html_decode($def));
    return True;
  }
}

#####################################################################################################

function reorder(&$sources)
{
  uasort($sources,"sort_source_order_compare");
  $i=1;
  foreach ($sources as $host => $params)
  {
    $sources[$host]["order"]=$i;
    $i++;
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
