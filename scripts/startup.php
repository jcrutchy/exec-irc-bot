<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED WHEN THE BOT ATTEMPTS TO IDENTIFY WITH NICKSERV

echo "################ BEGIN STARTUP ################\n";

set_bucket(BUCKET_CONNECTION_ESTABLISHED,"1");

#echo "/INTERNAL ~sedbot-ii\n";
#echo "/INTERNAL ~sedbot-tail0\n";

#sleep(12);

#echo "/INTERNAL ~sedbot-cmd /j #soylent\n";
#echo "/INTERNAL ~sedbot-cmd /j ##\n";
#echo "/INTERNAL ~sedbot-cmd /j #test\n";

#echo "/INTERNAL ~sedbot-awk1\n";
#echo "/INTERNAL ~sedbot-awk2\n";
#echo "/INTERNAL ~sedbot-awk3\n";

#echo "/INTERNAL ~sedbot-tail1\n";
#echo "/INTERNAL ~sedbot-tail2\n";
#echo "/INTERNAL ~sedbot-tail3\n";

#echo "/INTERNAL ~join #soylent\n";

echo "/INTERNAL ~join #comments\n";
echo "/INTERNAL ~join #github\n";

echo "/INTERNAL ~join #debug\n";
echo "/INTERNAL ~join #civ\n";

unset_bucket("<<EXEC_EVENT_HANDLERS>>");
echo "/INTERNAL ~civ register-events\n";
echo "/INTERNAL ~meeting register-events\n";
echo "/INTERNAL ~x register-events\n"; # LIVE SCRIPTING

register_event_handler("NICK",":".NICK_EXEC." INTERNAL :~verifier-nick-change %%nick%% %%trailing%%");

echo "/BUCKET_SET <<verifier_nick>> NetCraft\n";

echo "################# END STARTUP #################\n";

#####################################################################################################

?>
