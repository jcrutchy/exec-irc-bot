<?php

#####################################################################################################

/*
exec:add ~blah
exec:edit ~blah timeout 20
exec:edit ~blah accounts crutchy
exec:edit ~blah accounts_wildcard *
exec:edit ~blah cmds PRIVMSG
exec:edit ~blah servers banks.freenode.net
exec:edit ~blah dests #dogfart
exec:edit ~blah cmd php scripts/hanshenrik.php %%trailing%%
exec:enable ~blah
startup:~join #dogfart
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];

privmsg("butt");

#####################################################################################################

?>
