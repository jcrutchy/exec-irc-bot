<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED BEFORE THE IRC CONNECTION IS MADE, SO IRC COMMANDS AREN'T AVAILABLE
# TO EXECUTE A SCRIPT IN RESPONSE TO AN IRC EVENT, USE THE "cmd.php" SCRIPT

echo "################ BEGIN INIT ################\n";

#echo "/INTERNAL ~buckets-load\n";
sleep(4);
set_bucket(BUCKET_CONNECTION_ESTABLISHED,"0");

echo "################# END INIT #################\n";

#####################################################################################################

?>
