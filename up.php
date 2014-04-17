<?php

# gpl2
# by crutchy
# 17-april-2014

# 5/0/1/~up/php up.php "%%start%%"

echo "start=".$argv[1]."\n";
$uptime=microtime(True)-$argv[1];
echo "privmsg bacon up: ".round($uptime)." seconds\n";

?>
