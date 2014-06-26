<?php

# gpl2
# by crutchy
# 26-june-2014

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
        pm($nick,"exec bot alias help: ~");
        pm($nick,"  alias usage syntax: ~");
        pm($nick,"  outputs brief description of the exec bot, with links to github source and SoylentNews wiki page");
        break;
      default:
        pm($nick,"exec bot alias help syntax: ~help <alias>");
    }
    break;
  case "~help-script":
    switch ($trailing)
    {
      case "irc.php":
        pm($nick,"exec bot script help: irc.php");
        pm($nick,"  this file is the main irc bot script");
        break;
      default:
        pm($nick,"exec bot script help syntax: ~help-script <file>");
    }
    break;
}

#####################################################################################################

?>
