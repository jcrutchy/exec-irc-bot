<?php

#####################################################################################################

/*

exec:add ~butt
exec:edit ~butt cmd php scripts/000.php
exec:enable ~butt

*/

#####################################################################################################

require_once("lib.php");

#privmsg(get_bucket("process_template_nick"));
#privmsg(exec_alias_config_value("~moo","cmd"));

#privmsg(get_bucket("alias_element_~butt_fart"));

#notice("crutchy","fart");

#$data=get_user_localhost_ports();
#var_dump($data);

$users=get_array_bucket(BUCKET_USERS);
var_dump($users);

#####################################################################################################

?>
