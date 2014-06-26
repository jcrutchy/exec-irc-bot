<?php

# gpl2
# by crutchy
# 26-june-2014

# definitions.php

# http://api.urbandictionary.com/v0/define?term=shitton
# thanks weirdpercent

# https://encyclopediadramatica.es

#####################################################################################################

require_once("lib.php");
$msg=$argv[1];
$alias=$argv[2];
define("DEFINITIONS_FILE","../data/definitions");
define("MAX_DEF_LENGTH",200);
$terms=unserialize(file_get_contents(DEFINITIONS_FILE));
if ($alias=="~define-count")
{
  privmsg("custom definition count: ".count($terms));
  return;
}
if ($alias=="~define-sources")
{
  privmsg("definition sources in order of preference: www.urbandictionary.com > www.wolframalpha.com > www.stoacademy.com");
  return;
}
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
  else
  {
    privmsg("syntax: ~define-add <term> <definition>");
  }
  return;
}
foreach ($terms as $term => $def)
{
  $lterms[strtolower($term)]=$term;
}
if (isset($lterms[strtolower($msg)])==True)
{
  $def=$terms[$lterms[strtolower($msg)]];
  privmsg("[soylent] $msg: $def");
}
else
{
  if (wolframalpha($msg)==False)
  {
    if (urbandictionary($msg)==False)
    {
      if (stoacademy($msg)==False)
      {
        privmsg("$msg: unable to find definition");
      }
    }
  }
}

#####################################################################################################

function wolframalpha($msg)
{
  $html=wget("www.wolframalpha.com","/input/?i=define%3A".urlencode($msg),80);
  $html=strip_headers($html);
  if ((strpos($html,"Wolfram|Alpha doesn't know how to interpret your input.")!==False) or (strpos($html,"Using closest Wolfram|Alpha interpretation:")!==False))
  {
    term_echo("wolframalpha: term not defined");
    return False;
  }
  $delim1="context.jsonArray.popups.pod_0200.push( {\"stringified\": \"";
  $delim2="\",\"mInput\": \"\",\"mOutput\": \"\", \"popLinks\": {} });";
  $i=strpos($html,$delim1)+strlen($delim1);
  $html=substr($html,$i);
  $i=strpos($html,$delim2);
  $def=trim(substr($html,0,$i));
  if (strlen($def)>MAX_DEF_LENGTH)
  {
    $def=substr($def,0,MAX_DEF_LENGTH)."...";
  }
  if ($def=="")
  {
    return False;
  }
  else
  {
    privmsg("[wolframalpha] ".chr(3)."3$msg".chr(3).": $def");
    return True;
  }
}

#####################################################################################################

function urbandictionary($msg)
{
  # http://www.urbandictionary.com/define.php?term=Rule+34
  $html=wget("www.urbandictionary.com","/define.php?term=".urlencode($msg),80);
  $html2=strip_headers($html);
  if (strpos($html,"<i>".htmlspecialchars($msg)."</i> isn't defined.")!==False)
  {
    term_echo("urbandictionary: term not defined");
    return False;
  }
  $delim1="<div class='meaning'>";
  $delim2="</div>";
  $i=strpos($html2,$delim1);
  $html2=substr($html2,$i+strlen($delim1));
  $i=strpos($html2,$delim2);
  $def=trim(strip_tags(substr($html2,0,$i)));
  $def=str_replace(array("\n","\r")," ",$def);
  $def=str_replace("  "," ",$def);
  if (strlen($def)>MAX_DEF_LENGTH)
  {
    $def=substr($def,0,MAX_DEF_LENGTH)."...";
  }
  if ($def=="")
  {
    $location=exec_get_header($html,"location");
    if ($location=="")
    {
      return False;
    }
    else
    {
      return urbandictionary(extract_get($location,"term"));
    }
  }
  else
  {
    privmsg("[urbandictionary] ".chr(3)."3$msg".chr(3).": ".html_entity_decode($def,ENT_QUOTES,"UTF-8"));
    return True;
  }
}

#####################################################################################################

function stoacademy($msg)
{
  # http://www.stoacademy.com/datacore/dictionary.php?searchTerm=borg
  $html=wget("www.stoacademy.com","/datacore/dictionary.php?searchTerm=".urlencode($msg),80);
  $html2=strip_headers($html);
  if (strpos($html,"Sorry no results found.")!==False)
  {
    term_echo("stoacademy: term not defined");
    return False;
  }
  $delim1="<b><u>";
  $delim2="<p>";
  $i=strpos($html2,$delim1);
  $html2=substr($html2,$i+strlen($delim1));
  $i=strpos($html2,$delim2);
  $def=trim(strip_tags(substr($html2,0,$i)));
  $def=str_replace(array("\n","\r")," ",$def);
  $def=str_replace("  "," ",$def);
  if (strlen($def)>MAX_DEF_LENGTH)
  {
    $def=substr($def,0,MAX_DEF_LENGTH)."...";
  }
  if ($def=="")
  {
    return False;
  }
  else
  {
    privmsg("[stoacademy] ".chr(3)."3$msg".chr(3).": ".html_entity_decode($def,ENT_QUOTES,"UTF-8"));
    return True;
  }
}

#####################################################################################################

?>
