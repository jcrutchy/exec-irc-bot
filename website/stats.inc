<?php

# gpl2
# by crutchy
# 6-july-2014

#####################################################################################################

define("LOG_PATH","/var/www/irciv.us.to/exec_logs/");
define("TEMPLATE_FILE",__DIR__."/stats_template");
define("IRC_LIB_FILE",__DIR__."/irc_lib.php");

# __DIR__ = /var/include/vhosts/irciv.us.to/inc/website
if (file_exists("../irc_lib.php")==True)
{

}
else
{

}
return;

require_once(IRC_LIB_FILE);

$page=file_get_contents(TEMPLATE_FILE);
$page=str_replace("%%blah%%","blah",$page);
echo $page;

#####################################################################################################

?>
