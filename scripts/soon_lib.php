<?php

#####################################################################################################

define("TRANSLATIONS_FILE","soon_translations");

#####################################################################################################

function translate($pseudo_code,&$translations)
{
  # $pseudo_code = "hello x10"
  if (isset($translations[$pseudo_code])==False)
  {
    $pseudo_code=$translations[$pseudo_code];
  }
  # $pseudo_code = loop 10 msg "hello"
  # loop 10 msg "hello" >> loop n code
  # work out which parts of the pseudo code are constant
  foreach ($translations as $key => $value)
  {

  }
  return False;
}

#####################################################################################################

function map_pseudo_code($pseudo_code,$key)
{
  $subject_parts=explode(" ",$pseudo_code);
  $test_map=array();
  $parts=explode(" ",$key);
  for ($i=0;$i<count($parts);$i++)
  {
    if (strpos($value,$parts[$i])===False)
    {
      $test_map[]=$parts[$i];
    }
    else
    {
      $test_map[]="%";
    }
  }
  $subject_map=$subject_parts;
  if (count($test_map)<count($subject_map))
  {
    # reduce size of $subject_map
  }
  if (count($test_map)>count($subject_map))
  {
    # reduce size of $test_map
  }
  $match=True;
  $mapped=array();
  for ($i=0;$i<count($test_map);$i++)
  {
    if ($test_map[$i]=="%")
    {
      $mapped[]=$subject_map[$i];
      continue;
    }
    if ($test_map[$i]<>$subject_map[$i])
    {
      $match=False;
      break;
    }
    else
    {
      $mapped[]=$test_map[$i];
    }
  }
  if ($match==True)
  {
    $pseudo_code=implode(" ",$mapped);
    return translate($pseudo_code,$translations);
  }
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
    return False;
  }
  $data=explode("\n",$data);
  $translations="";
  for ($i=0;$i<count($data);$i++)
  {
    $parts=explode(">>",$data[$i]);
    if (count($parts)>=2)
    {
      term_echo("*** INVALID TRANSLATION: ".$data[$i]);
      return False;
    }
    $translations[trim($parts[0])]=trim($parts[1]);
  }
  return $translations;
}

#####################################################################################################

?>
