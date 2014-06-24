<?php

# gpl2
# by crutchy
# 24-june-2014

#####################################################################################################

require_once("lib.php"); # also requires lib.php

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];

switch ($cmd)
{
  case "PRIVMSG":
    #echo ":$nick INTERNAL $dest :~sed $trailing\n";
    break;
}

#####################################################################################################

?>
