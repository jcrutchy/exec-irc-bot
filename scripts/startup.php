<?php

# gpl2
# by crutchy
# 22-july-2014

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED WHEN THE BOT ATTEMPTS TO IDENTIFY WITH NICKSERV

echo "################ BEGIN STARTUP ################\n";
echo "/INTERNAL ~sedbot-ii\n";
sleep(8);
echo "/IRC :".NICK_EXEC." INTERNAL :~sedbot-cmd /j #soylent\n";
echo "/IRC :".NICK_EXEC." INTERNAL : ~sedbot-cmd /j ##\n";
echo "/IRC :".NICK_EXEC." INTERNAL : ~sedbot-awk1\n";
echo "/IRC :".NICK_EXEC." INTERNAL : ~sedbot-awk2\n";
echo "/IRC :".NICK_EXEC." INTERNAL : ~sedbot-tail1\n";
echo "/IRC :".NICK_EXEC." INTERNAL : ~sedbot-tail2\n";
echo "/IRC :".NICK_EXEC." INTERNAL : ~join #soylent\n";
echo "################# END STARTUP #################\n";

#####################################################################################################

?>
