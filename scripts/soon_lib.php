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

function map_pseudo_code($pseudo_code,$key,$value)
{
  # loop 10 msg \"hello\" ==> loop n code >> for (\$i=1;\$i<=n;\$i++) { code }

  # create a map for key >> value
  # loop n code >> for (\$i=1;\$i<=n;\$i++) { code }

  # "loop" => ""
  # "n" => "%"
  # "code" => "%"

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

  $code_parts=explode(" ",$pseudo_code);

  if (count($key_parts)>count($code_parts))
  {
    # not enough parts in pseudo_code (incompatible with key)
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

  $i=0;
  foreach ($map as $key => $value)
  {
    if (($value=="") and ($key<>$code_parts[$i]))
    {
      # fixed syntax mismatch
      return False;
    }
    if ($value=="%")
    {
      $map[$key]=$code_parts[$i];
    }
    $i++;
  }

  var_dump($map);

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
