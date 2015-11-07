<?php

#####################################################################################################

/*
exec:~wget|20|0|0|1|||||php scripts/wget.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
require_once("wget_lib.php");

$trailing=trim($argv[1]);

if ($trailing=="")
{
  privmsg("syntax: ~wget url delim 1 <> delim 2");
  return;
}

$result=quick_wget($trailing);

if ($trailing==False)
{
  privmsg("syntax: ~wget url delim 1 <> delim 2");
  return;
}

if (strlen($result)>300)
{
  $result=trim(substr($result,0,300))."...";
}

privmsg(chr(3)."07".$result);

#####################################################################################################

?>
