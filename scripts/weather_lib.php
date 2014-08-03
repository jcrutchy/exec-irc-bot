<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

define("CODES_FILE","../data/weather.codes");

#####################################################################################################

function load_codes()
{
  if (file_exists(CODES_FILE)==False)
  {
    term_echo("*** LOCATION CODES FILE NOT FOUND ***");
    return False;
  }
  $codes=file_get_contents(CODES_FILE);
  if ($codes===False)
  {
    return False;
  }
  $codes=unserialize($codes);
  if ($codes===False)
  {
    return False;
  }
  return $codes;
}

#####################################################################################################

function get_location($code)
{
  $codes=load_codes();
  if ($codes===False)
  {
    return False;
  }
  $code=strtolower(trim($code));
  if (isset($codes[$code])==True)
  {
    return $codes[$code];
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function set_location($code,$location)
{
  $codes=load_codes();
  if ($codes===False)
  {
    return False;
  }
  $code=strtolower(trim($code));
  $location=trim($location);
  $codes[$code]=$location;
  if (file_put_contents(CODES_FILE,serialize($codes))===False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

?>
