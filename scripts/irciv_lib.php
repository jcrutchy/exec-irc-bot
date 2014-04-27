<?php

# gpl2
# by crutchy
# 27-april-2014

# irciv_lib.php

define("GAME_NAME","IRCiv");
define("NICK_EXEC","exec");

#####################################################################################################

function irciv__term_echo($msg)
{
  echo GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__privmsg($msg)
{
  echo "IRC_MSG ".GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__err($msg)
{
  echo "IRC_MSG ".GAME_NAME." error: $msg\n";
  die();
}

#####################################################################################################

function get_bucket()
{
  global $bucket;
  echo ":".NICK_EXEC." BUCKET_GET :\$bucket[\"civ\"]\n";
  $f=fopen("php://stdin","r");
  $line=fgets($f);
  if ($line===False)
  {
    irciv__err("unable to read bucket data");
  }
  else
  {
    $line=trim($line);
    if (($line<>"") and ($line<>"NO BUCKET DATA FOR WRITING TO STDIN") and ($line<>"BUCKET EVAL ERROR"))
    {
      echo "$line\n";
      $tmp=unserialize(gzuncompress($line));
      if ($tmp!==False)
      {
        $bucket["civ"]=$tmp;
        irciv__term_echo("successfully loaded bucket data");
      }
      else
      {
        irciv__term_echo("error unserializing bucket data");
      }
    }
    else
    {
      irciv__term_echo("no bucket data to load");
    }
  }
  fclose($f);
}

#####################################################################################################

function set_bucket()
{
  global $bucket;
  $data=gzcompress(serialize($bucket));
  echo ":".NICK_EXEC." BUCKET_SET :$data\n";
}

#####################################################################################################

?>
