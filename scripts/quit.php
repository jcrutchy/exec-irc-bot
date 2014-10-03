<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");

# THIS SCRIPT IS CALLED BY ~q BUT NOT WHEN EXEC QUITS DUE TO BAD ~rehash
# IRC COMMANDS ARE STILL AVAILABLE, AND EXEC WILL NOT QUIT UNTIL <<quit>> DIRECTIVE IS ECHOED
# IF <<quit>> DIRECTIVE IS NOT ECHOED, EXEC WILL NOT QUIT (WILL NEED TO BE FORCED USING CRTL+C IN TERMINAL)

echo "################ BEGIN QUIT ################\n";
echo "################# END QUIT #################\n";

echo "/INTERNAL ~buckets-save\n";
sleep(5);

echo "<<quit>>\n";

#####################################################################################################

?>
