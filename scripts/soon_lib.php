<?php

#####################################################################################################

define("TRANSLATIONS_FILE",__DIR__."/soon_translations");

#####################################################################################################

function translate(&$translations)
{
  #$build=build("hellox10",$translations);

  $build=build("msg \"hello\"",$translations);

  #var_dump($build);
}

#####################################################################################################

function build($pseudo_code,&$translations)
{
  if (isset($translations[$pseudo_code])==True)
  {
    $pseudo_code=$translations[$pseudo_code];
  }
  foreach ($translations as $translation_key => $translation_value)
  {
    $map=map($pseudo_code,$translation_key,$translation_value);
    if ($map===False)
    {
      continue;
    }
    var_dump($map);
    assemble($map,$translation_value,$translations);
    return $map;
  }
  return False;
}

#####################################################################################################

function assemble(&$map,$value,&$translations)
{
  foreach ($map as $map_key => $map_value)
  {
    if ($map_value=="")
    {
      continue;
    }
    foreach ($translations as $translation_key => $translation_value)
    {
      $sub_map=map($map_value,$translation_key,$translation_value);
      if ($sub_map===False)
      {
        continue;
      }
      assemble($sub_map,$translation_value,$translations);
      $map[$map_key]=$sub_map;
    }
  }
}

#####################################################################################################

/*
map_pseudo_code("loop 10 msg \"hello\"","loop n code","for (\$i=1;\$i<=n;\$i++) { code }");
array(3) {
  ["loop"]=> string(0) ""
  ["n"]=> string(1) "%"
  ["code"]=> string(1) "%"
}
array(3) {
  ["loop"]=> string(0) ""
  ["n"]=> string(2) "10"
  ["code"]=> string(11) "msg "hello""
}
*/

function map($pseudo_code,$key,$value)
{
  # example: map_pseudo_code("loop 10 msg \"hello\"","loop n code","for (\$i=1;\$i<=n;\$i++) { code }");
  # create a map for key >> value
  $map=array();
  $key_parts=explode(" ",$key);
  for ($i=0;$i<count($key_parts);$i++)
  {
    if (strpos($value,$key_parts[$i])===False)
    {
      $map[$key_parts[$i]]="";
    }
    else
    {
      $map[$key_parts[$i]]="%";
    }
  }
  var_dump($map);
/*
array(3) {
  ["loop"]=> string(0) ""
  ["n"]=> string(1) "%"
  ["code"]=> string(1) "%"
}
*/
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
/*
array(3) {
  ["loop"]=> string(0) ""
  ["n"]=> string(2) "10"
  ["code"]=> string(11) "msg "hello""
}
*/
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
