<?php

# gpl2
# by crutchy
# 5-aug-2014

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

echo "/INTERNAL ~civ startup\n";

echo "################# END STARTUP #################\n";

#####################################################################################################

?>
