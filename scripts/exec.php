<?php

# gpl2
# by crutchy

#####################################################################################################

$trailing=trim($argv[1]);
$alias=trim($argv[2]);

switch ($alias)
{
  case "~exec-add":
    echo "/EXEC-ADD $trailing\n";
    return;
  case "~exec-del":
    echo "/EXEC-DEL $trailing\n";
    return;
  case "~exec-save":
    echo "/EXEC-SAVE $trailing\n";
    return;
}

#####################################################################################################

?>
