<?php

# gpl2
# by crutchy
# 8-aug-2014

# http://wiki.soylentnews.org/wiki/IRC:exec_aliases

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

switch ($alias)
{
  case "~help":
    switch ($trailing)
    {
      case "~":
        privmsg("exec bot alias help: ~");
        privmsg("  alias usage syntax: ~");
        privmsg("  outputs brief description of the exec bot, with links to github source and SoylentNews wiki page");
        break;
      default:
        privmsg("exec bot alias help syntax: ~help <alias>");
    }
    break;
  case "~help-script":
    switch ($trailing)
    {
      case "irc.php":
        privmsg("exec bot script help: irc.php");
        privmsg("  this file is the main irc bot script");
        break;
      default:
        privmsg("exec bot script help syntax: ~help-script <file>");
    }
    break;
}

#####################################################################################################

?>
