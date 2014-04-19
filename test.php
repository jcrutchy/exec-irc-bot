<?php

# gpl2
# by crutchy
# 20-april-2014

# 5|0|0|test|php test.php %%msg%% %%chan%% %%nick%%

/*
array(4) {
  [0]=>
  string(10) "script.php"
  [1]=>
  string(4) "arg1"
  [2]=>
  string(4) "arg2"
  [3]=>
  string(4) "arg3"
}
*/

echo "this shows on term only\n";
echo "privmsg msg=".$argv[1]."\n";
echo "privmsg chan=".$argv[2]."\n";
echo "privmsg nick=".$argv[3]."\n";

?>
