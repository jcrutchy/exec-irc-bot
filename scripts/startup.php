<?php

# gpl2
# by crutchy
# 24-july-2014

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED WHEN THE BOT ATTEMPTS TO IDENTIFY WITH NICKSERV

echo "################ BEGIN STARTUP ################\n";

echo "/INTERNAL ~sedbot-ii\n";
echo "/INTERNAL ~sedbot-tail0\n";

sleep(12);

echo "/INTERNAL ~sedbot-cmd /j #soylent\n";
echo "/INTERNAL ~sedbot-cmd /j ##\n";
echo "/INTERNAL ~sedbot-cmd /j #test\n";

echo "/INTERNAL ~sedbot-awk1\n";
echo "/INTERNAL ~sedbot-awk2\n";
echo "/INTERNAL ~sedbot-awk3\n";

echo "/INTERNAL ~sedbot-tail1\n";
echo "/INTERNAL ~sedbot-tail2\n";
echo "/INTERNAL ~sedbot-tail3\n";

echo "/INTERNAL ~join #soylent\n";

pm("#Soylent","SedBot");
pm("##","SedBot");
pm("#test","SedBot");

pm("#Soylent","s/SedBot/hello/");
pm("##","s/SedBot/hello/");
pm("#test","s/SedBot/hello/");

echo "################# END STARTUP #################\n";

#####################################################################################################

?>
