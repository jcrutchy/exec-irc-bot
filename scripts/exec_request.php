<?php

# gpl2
# by crutchy
# 13-july-2014

#####################################################################################################

require_once("lib.php");

$trailing=strip_ctrl_chars(trim(substr($argv[1],0,200)));
$nick=$argv[2];
$dest=$argv[3];

define("REQUESTS_FILE","../data/requests");

$timestamp=date("Y-m-d H:i:s",microtime(True));

$line="[$timestamp] <$nick> $dest :$trailing\n";

if (file_put_contents(REQUESTS_FILE,$line,FILE_APPEND)==True)
{
  privmsg("*** successfully appended request");
}
else
{
  privmsg("*** error appending request");
}

#####################################################################################################

?>
