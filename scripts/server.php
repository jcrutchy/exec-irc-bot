<?php

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

# http://php.net/manual/en/function.socket-create.php
# http://www.php.net/manual/en/function.socket-bind.php
# http://www.php.net/manual/en/function.socket-listen.php
# http://www.php.net/manual/en/function.socket-accept.php

# http://www.funphp.com/?p=33 [accessed 28-aug-2014]

set_time_limit(0);
$address="127.0.0.1";
$port=6789;
$socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if (socket_bind($socket,$address,$port)==False)
{
  die("error");
}
socket_listen($socket);
while (True)
{
  $client=socket_accept($socket);
  $input=socket_read($client,1024000);
  $response="pants\n";
  socket_write($client,$response);
  socket_close($client);
}
socket_close($socket);

#####################################################################################################

?>
