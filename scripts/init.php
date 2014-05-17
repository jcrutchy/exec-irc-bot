<?php

# gpl2
# by crutchy
# 16-may-2014

#####################################################################################################

ini_set("display_errors","on");

# THIS SCRIPT IS CALLED BEFORE THE IRC CONNECTION IS MADE, SO IRC COMMANDS AREN'T AVAILABLE
# TO EXECUTE A SCRIPT IN RESPONSE TO AN IRC EVENT, USE THE "cmd.php" SCRIPT

echo "################ BEGIN INIT ################\n";
echo ":exec BUCKET_SET :init success\n";

require_once("irciv_lib.php");
irciv_load_data();

echo "################# END INIT #################\n";

#####################################################################################################

?>
