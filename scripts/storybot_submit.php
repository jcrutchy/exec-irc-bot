<?php

#####################################################################################################

/*
exec:add ~submit-story
exec:edit ~submit-story timeout 30
exec:edit ~submit-story repeat 3600
exec:edit ~submit-story accounts_wildcard *
exec:edit ~submit-story servers irc.sylnt.us
exec:edit ~submit-story cmd php scripts/storybot_submit.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:enable ~submit-story
help ~submit-story|syntax: ~submit-story <id>
help ~submit-story|submits a story with id from list at http://ix.io/ACx
*/

#####################################################################################################

date_default_timezone_set("UTC");

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

$stories_path="/home/jared/git/storybot/Stories/";
$keep_days=3;

if ($trailing=="list")
{
  refresh_list();
  privmsg("http://ix.io/ACx");
  return;
}

refresh_list();

delete_old();

#####################################################################################################

function refresh_list()
{
  global $stories_path;
  $file_list=scandir($stories_path);
  $data=array();
  $data[]="id\tfilename";
  $id=1;
  for ($i=0;$i<count($file_list);$i++)
  {
    $filename=$file_list[$i];
    if (($filename==".") or ($filename==".."))
    {
      continue;
    }
    $id++;
    $data[]="$id\t$filename";
  }
  $data=implode(PHP_EOL,$data);
  output_ixio_paste($data,False,"ACx");
}

#####################################################################################################

function delete_old()
{
  global $stories_path;
  global $keep_days;
  $file_list=scandir($stories_path);
  $datum=time();
  for ($i=0;$i<count($file_list);$i++)
  {
    $filename=$stories_path.$file_list[$i];
    $t=filemtime($filename);
    if (($datum-$t)>($keep_days*24*60*60))
    {
      if (@unlink($filename)===False)
      {
        term_echo("storybot: ERROR DELETING OLD FILE \"".$filename."\"");
      }
      else
      {
        term_echo("storybot: deleted old file \"".$filename."\"");
      }
    }
  }
}

#####################################################################################################

?>
