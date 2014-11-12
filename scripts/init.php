<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED BEFORE THE IRC CONNECTION IS MADE, SO IRC COMMANDS AREN'T AVAILABLE
# TO EXECUTE A SCRIPT IN RESPONSE TO AN IRC EVENT, USE THE "cmd.php" SCRIPT

echo "################ BEGIN INIT ################\n";

set_bucket(BUCKET_CONNECTION_ESTABLISHED,"0");
unset_bucket(BUCKET_USERS);

echo "################# END INIT #################\n";

#####################################################################################################

?>
