<?php

#####################################################################################################

/*
exec:add ~avr
exec:edit ~avr timeout 0
exec:edit ~avr dests #crutchy
exec:edit ~avr cmd php scripts/avr.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%% %%server%%
exec:enable ~avr
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];
$server=$argv[6];

switch ($trailing)
{
  case "start":
    set_bucket("avr_rs232_enabled","1");
    $previous="";
    $fd=dio_open("/dev/ttyS0",O_RDWR|O_NOCTTY|O_NONBLOCK);
    dio_fcntl($fd,F_SETFL,O_SYNC);
    dio_tcsetattr($fd,array("baud"=>9600,"bits"=>8,"stop"=>1,"parity"=>0));
    while (get_bucket("avr_rs232_enabled")<>"")
    {
      $data=trim(dio_read($fd,256));
      if ($data<>$previous)
      {
        term_echo($data);
      }
      $previous=$data;
    }
    dio_close($fd);
    break;
  case "stop":
    unset_bucket("avr_rs232_enabled");
    break;
}

#####################################################################################################

?>
