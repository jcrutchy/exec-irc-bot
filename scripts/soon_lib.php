<?php

#####################################################################################################

define("TRANSLATIONS_FILE",__DIR__."/soon_translations");

#####################################################################################################

function translate(&$translations,$pseudo_code)
{
  $map=array();
  map_recurse($translations,$pseudo_code,$map);
  var_dump($map);
  #$code=code_assemble($map);
  #var_dump($code);
}

#####################################################################################################

function code_assemble($map)
{
  $code=$map[1];
  foreach ($map as $key => $value)
  {
    if (($key==0) or ($key==1))
    {
      continue;
    }
    if (is_array($value)==True)
    {
      $value=code_assemble($value);
    }
    else
    {

    }
  }
}

#####################################################################################################

function map_recurse(&$translations,$pseudo_code,&$map)
{
  if (isset($translations[$pseudo_code])==True)
  {
    $pseudo_code=$translations[$pseudo_code];
  }
  foreach ($translations as $translation_key => $translation_value)
  {
    $sub_map=map_translation($pseudo_code,$translation_key,$translation_value);
    if ($sub_map===False)
    {
      continue;
    }
    foreach ($sub_map as $map_key => $map_value)
    {
      if ($map_value=="")
      {
        continue;
      }
      map_recurse($translations,$map_value,$sub_map);
    }
    array_unshift($sub_map,$translation_key,$translation_value);
    $map[$map_key]=$sub_map;
  }
}

#####################################################################################################

function ident_exists($str,$ident)
{
  $id="";
  for ($i=0;$i<strlen($str);$i++)
  {
    $n=ord($str[$i]);
    switch (True)
    {
      case in_array($n,range(48,57)): # 0-9
      case in_array($n,range(65,90)): # A-Z
      case in_array($n,range(97,122)): # a-z
      case ($n==95): # _
        $id=$id.$str[$i];
        break;
      default:
        $id="";
    }
    if ($id==$ident)
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function map_translation($pseudo_code,$key,$value)
{
  # create a map for key >> value
  $map=array();
  $key_parts=explode(" ",$key);
  $value_parts=explode(" ",$value);
  for ($i=0;$i<count($key_parts);$i++)
  {
    if (ident_exists($value,$key_parts[$i])==False)
    {
      $map[$key_parts[$i]]="";
    }
    else
    {
      $map[$key_parts[$i]]="%";
    }
  }
  $code_parts=explode(" ",$pseudo_code);
  if (count($key_parts)>count($code_parts))
  {
    term_echo("*** MAPPING ERROR: not enough parts in pseudo_code (incompatible with key)");
    return False;
  }
  if (count($key_parts)<count($code_parts))
  {
    # reduce size of $code_parts
    $tmp=array();
    for ($j=0;$j<(count($key_parts)-1);$j++)
    {
      $tmp[]=array_shift($code_parts);
    }
    $tmp[]=implode(" ",$code_parts);
    $code_parts=$tmp;
  }
  # match parts of pseudo_code with key map
  $i=0;
  foreach ($map as $key => $value)
  {
    if (($value=="") and ($key<>$code_parts[$i]))
    {
      term_echo("*** MAPPING ERROR: fixed syntax mismatch");
      return False;
    }
    if ($value=="%")
    {
      $map[$key]=$code_parts[$i];
    }
    $i++;
  }
  return $map;
}

#####################################################################################################

function load_translations()
{
  if (file_exists(TRANSLATIONS_FILE)==False)
  {
    term_echo("*** TRANSLATIONS FILE NOT FOUND: ".TRANSLATIONS_FILE);
    return False;
  }
  $data=file_get_contents(TRANSLATIONS_FILE);
  if ($data===False)
  {
    term_echo("*** ERROR LOADING TRANSLATIONS FILE: ".TRANSLATIONS_FILE);
    return False;
  }
  $data=explode("\n",$data);
  $translations="";
  for ($i=0;$i<count($data);$i++)
  {
    $line=trim($data[$i]);
    if ($line=="")
    {
      continue;
    }
    $parts=explode(">>",$line);
    if (count($parts)<2)
    {
      term_echo("*** INVALID TRANSLATION: ".$line);
      return False;
    }
    $translations[trim($parts[0])]=trim($parts[1]);
  }
  return $translations;
}

#####################################################################################################

?>
