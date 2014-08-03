<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

require_once("lib.php");
date_default_timezone_set("UTC");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

if (($dest<>"#") or ($nick<>"crutchy"))
{
  return;
}

$password=trim(file_get_contents("../pwd/server"));
$SID="44X";

$socket=fsockopen("192.168.1.61",6667);
if ($socket===False)
{
  term_echo("SERVER: ERROR OPENING SOCKET CONNECTION");
  return;
}
else
{
  stream_set_blocking($socket,0);
  fputs($socket,"PASS $password TS 6 $SID\n");
  fputs($socket,"CAPAB :QS ENCAP EX CHW IE KNOCK SAVE EUID SERVICES RSFNC KLN UNKLN TB EOPMOD MLOCK\n");
  fputs($socket,"SERVER exec.lan 0 :exec services\n");
  fputs($socket,"SVINFO 6 6 0 ".time()."\n");
}

while (True)
{
  $data=fgets($socket);
  echo $data;
  usleep(0.01e6); # 0.01 second to prevent cpu flogging
}

#####################################################################################################

?>
