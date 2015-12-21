<?php

#####################################################################################################

/*
exec:~git|20|0|0|1|crutchy|PRIVMSG|#exec||php scripts/git.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "pull":
    $output=explode(PHP_EOL,trim(shell_exec("git pull 2>&1")));
    for ($i=0;$i<count($output);$i++)
    {
      privmsg($output[$i]);
    }
    break;
  default:
    break;
}

#####################################################################################################

?>