<?php

#####################################################################################################

/*
exec:~startup|60|0|0|1|@||||php scripts/startup.php
*/

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED WHEN THE BOT ATTEMPTS TO IDENTIFY WITH NICKSERV

echo "################ BEGIN STARTUP ################\n";

#rawmsg("JOIN #");
rawmsg("MODE #comments -o crutchy");
rawmsg("MODE #github -o crutchy");
rawmsg("MODE #feeds -o crutchy");
rawmsg("MODE #freenode -o crutchy");
echo "/INTERNAL ~freenode\n";

echo "################# END STARTUP #################\n";

#####################################################################################################

?>
