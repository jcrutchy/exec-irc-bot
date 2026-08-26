<?php

#####################################################################################################

/*
#exec:~ircd|20|0|0|1|crutchy||||php scripts/ircd.php %%trailing%% %%dest%% %%nick%%
*/

/*
connect "lappy" {
	host = "192.168.1.22";
	send_password = "send_password";
	accept_password = "accept_password";
	port = 6667;
	hub_mask = "*";
	class = "server";
	flags = topicburst;
*/

#####################################################################################################

require_once("lib.php");
date_default_timezone_set("UTC");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

if (($dest<>"#sylnt") or ($nick<>"crutchy"))
{
  return;
}

#$password=trim(file_get_contents("../pwd/server"));
$password="accept_password";
$SID="44X";
$server_name="192.168.1.22";
$server_description="jared's lappy";

$socket=fsockopen("192.168.1.55",6667);
if ($socket===False)
{
  term_echo("SERVER: ERROR OPENING SOCKET CONNECTION");
  return;
}
else
{
  stream_set_blocking($socket,0);
  fputs($socket,"PASS $password TS 6 :$SID\n");
  # https://github.com/atheme/charybdis/blob/master/doc/technical/capab.txt
  #fputs($socket,"CAPAB :QS ENCAP EX CHW IE KNOCK SAVE EUID SERVICES RSFNC KLN UNKLN TB EOPMOD MLOCK\n");
  fputs($socket,"CAPAB :TB\n");
  fputs($socket,"SERVER $server_name 1 :$server_description\n");
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
