<?php

#####################################################################################################

/*
exec:~butt|10|0|0|1|@||||php scripts/000.php

exec:edit ~butt fart smelly # TODO: USING MACROS ON A PRECEDING EXEC LINE ALIAS IS BORKED
exec:enable ~butt

exec:add ~butt2
exec:edit ~butt2 cmd apt-get moo
exec:edit ~butt2 auto 1
exec:enable ~butt2

*/

#####################################################################################################

require_once("lib.php");

#privmsg(get_bucket("process_template_nick"));
#privmsg(exec_alias_config_value("~moo","cmd"));

privmsg(get_bucket("alias_element_~butt_fart"));

#####################################################################################################

?>
