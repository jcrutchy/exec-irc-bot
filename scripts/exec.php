<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~exec-add|5|0|0|0|@|||0|php scripts/exec.php %%trailing%% %%alias%%
exec:~exec-del|5|0|0|0|@|||0|php scripts/exec.php %%trailing%% %%alias%%
exec:~exec-save|5|0|0|0|@|||0|php scripts/exec.php %%trailing%% %%alias%%
*/

#####################################################################################################

$trailing=trim($argv[1]);
$alias=trim($argv[2]);

switch ($alias)
{
  case "~exec-add":
    echo "/EXEC-ADD $trailing\n";
    return;
  case "~exec-del":
    echo "/EXEC-DEL $trailing\n";
    return;
  case "~exec-save":
    echo "/EXEC-SAVE $trailing\n";
    return;
}

#####################################################################################################

?>
