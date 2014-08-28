<?php

# gpl2
# by crutchy
# 28-aug-2014

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$start=$argv[4];
$alias=$argv[5];
$cmd=$argv[6];
$data=$argv[7];
$exec=$argv[8];
$params=$argv[9];

# http://php.net/manual/en/function.socket-create.php
# http://www.php.net/manual/en/function.socket-bind.php
# http://www.php.net/manual/en/function.socket-listen.php
# http://www.php.net/manual/en/function.socket-accept.php

# http://www.funphp.com/?p=33 [accessed 28-aug-2014]

set_time_limit (0);
// Set the ip and port we will listen on
$address = '127.0.0.1';
$port = 6789;
// Create a TCP Stream socket
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
// Bind the socket to an address/port
socket_bind($sock, $address, $port) or die('Could not bind to address');  //0 for localhost
// Start listening for connections
socket_listen($sock);
//loop and listen
while (true) {
/* Accept incoming  requests and handle them as child processes */
$client =  socket_accept($sock);
// Read the input  from the client – 1024000 bytes
$input =  socket_read($client, 1024000);
// Strip all white  spaces from input
$output =  ereg_replace("[ \t\n\r]","",$input)."\0";
$message=explode('=',$output);
if(count($message)==2)
{
if(get_new_order()) $response='NEW:1';
else  $response='NEW:0';
}
else $response='NEW:0';
// Display output  back to client
socket_write($client, $response);
socket_close($client);
}
// Close the master sockets
socket_close($sock);

#####################################################################################################

?>
