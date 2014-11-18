<?php

# gpl2
# by crutchy

#####################################################################################################

function var_get_path_delim($path)
{
  $delims="$./\\>";
  $delim="";
  for ($i=0;$i<strlen($path);$i++)
  {
    if (strpos($delims,$path[$i])!==False)
    {
      $delim=$path[$i];
      break;
    }
  }
  return $delim;
}

#####################################################################################################

?>
