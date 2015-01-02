<?php

#####################################################################################################

function cmd_mode($client_index,$items)
{
  # MODE #stuff
  # :irc.sylnt.us MODE #stuff +nt
  $params=$items["params"];
  $msg=":".SERVER_HOSTNAME." MODE $params +nt";
  do_reply($client_index,$msg);
}

#####################################################################################################

?>
