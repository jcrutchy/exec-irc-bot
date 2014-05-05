<?php

# gpl2
# by crutchy
# 5-may-2014

#####################################################################################################

ini_set("display_errors","on");

# THIS SCRIPT IS CALLED BY ~q BUT NOT WHEN EXEC QUITS DUE TO BAD ~reload
# IRC COMMANDS ARE STILL AVAILABLE, AND EXEC WILL NOT QUIT UNTIL <<quit>> DIRECTIVE IS ECHOED
# IF <<quit>> DIRECTIVE IS NOT ECHOED, EXEC WILL NOT QUIT (WILL NEED TO BE FORCED USING CRTL+C IN TERMINAL)

echo "################ BEGIN QUIT ################\n";
echo "################# END QUIT #################\n";
echo "<<quit>>\n";

#####################################################################################################

?>
