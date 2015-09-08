<?php

#####################################################################################################

# "soon" is intended to be a sort of macro processor that can translate pseudo-code to php

#####################################################################################################

/*
exec:~soon|30|0|0|1|@||||php scripts/soon/soon.php
*/

#####################################################################################################

ini_set("display_errors","on");

require_once(__DIR__."/../lib.php");
require_once("soon_lib.php");

$dest=get_bucket("process_template_destination");

if ($dest<>"#irciv")
{
  return;
}

$translations=load_translations();

var_dump(soon_explode("for ($i=1;$i<=n;$i++) { code }"));

#soon_test("loop 3 fart","loop n code","for ($i=1;$i<=n;$i++) { code }","for ($i=1;$i<=3;$i++) { fart }");

#####################################################################################################

function soon_test($code,$key,$value,$result)
{
  $map=map_key($code,$key);
}

#####################################################################################################

function map_key($code,$key)
{
  # code = loop 3 fart
  # key = loop n code
  $code_parts=soon_explode($code);
  $key_parts=soon_explode($key);
}

#####################################################################################################

function soon_explode($code)
{
  # delimit whenever there is a change from alphanumeric to anything else
  $alphanum=VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC;
  $parts=array();
  $n=strlen($code);
  if ($n>0)
  {
    $c=$code[0];
    $tok=$c;
    for ($i=1;$i<$n;$i++)
    {
      if (((strpos($alphanum,$code[$i])!==False) and (strpos($alphanum,$c)===False)) or ((strpos($alphanum,$code[$i])===False) and (strpos($alphanum,$c)!==False)))
      {
        $parts[]=$tok;
        $tok=$code[$i];
      }
      else
      {
        $tok=$tok.$code[$i];
      }
      $c=$code[$i];
    }
  }
  if (strlen($tok)>0)
  {
    $parts[]=$tok;
  }
  # except where two numbers are separated by a decimal point
  
  return $parts;
}

#####################################################################################################

?>
