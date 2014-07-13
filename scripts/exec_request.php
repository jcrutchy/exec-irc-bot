<?php

# gpl2
# by crutchy
# 13-july-2014

#####################################################################################################

require_once("lib.php");

$trailing=strip_ctrl_chars(trim(substr($argv[1],0,200)));
$nick=$argv[2];
$dest=$argv[3];

define("IDEAS_FILE","ideas");

if (file_exists(IDEAS_FILE)==False)
{
  privmsg("ideas file not found");
  return;
}

$timestamp=date("Y-m-d H:i:s",microtime(True));

$line="[$timestamp] <$nick> $dest :$trailing\n";

file_put_contents(IDEAS_FILE,$line,FILE_APPEND);

#####################################################################################################

?>
