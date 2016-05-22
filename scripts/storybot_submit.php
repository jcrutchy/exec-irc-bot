<?php

#####################################################################################################

/*
exec:add ~submit-story
exec:edit ~submit-story timeout 60
exec:edit ~submit-story repeat 3600
exec:edit ~submit-story accounts_wildcard *
exec:edit ~submit-story servers irc.sylnt.us
exec:edit ~submit-story cmd php scripts/storybot_submit.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
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
$cmd=$argv[5];

$submit_host="dev.soylentnews.org";

$stories_path="/home/jared/git/storybot/Stories/";

$stories_path_filename=DATA_PATH."storybot_path.txt";

if (file_exists($stories_path_filename)==True)
{
  $stories_path_test=file_get_contents($stories_path_filename);
  if (($stories_path_test!==False) and (file_exists($stories_path_test)==True) and (is_dir($stories_path_test)==True))
  {
    $stories_path=rtrim($stories_path_test,"/")."/";
  }
}

$keep_days=3;

if ($trailing=="list")
{
  refresh_list();
  privmsg("http://ix.io/ACx");
  return;
}

if ($cmd<>"INTERNAL")
{
  submit_story($trailing);
  return;
}

delete_old();

refresh_list();

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

function submit_story($id)
{
  global $stories_path;
  global $submit_host;
  $response=wget("ix.io","/ACx",80);
  $content=trim(strip_headers($response));
  $items=explode(PHP_EOL,$content);
  $story_filename="";
  for ($i=1;$i<count($items);$i++)
  {
    $item=$items[$i];
    $parts=explode("\t",$item);
    if (count($parts)<>2)
    {
      continue;
    }
    $test_id=$parts[0];
    $test_filename=$parts[1];
    if ($id<>$test_id)
    {
      continue;
    }
    $story_filename=$stories_path.$test_filename;
    break;
  }
  if (file_exists($story_filename)==False)
  {
    privmsg("file \"$story_filename\" not found");
    return;
  }
  $data=file_get_contents($story_filename);
  if ($data===False)
  {
    term_echo("error reading file \"$story_filename\"");
    return;
  }
  $source_title=str_replace("_"," ",$test_filename);
  $i=strpos($source_title,"--");
  if ($i!==False)
  {
    $source_title=trim(substr($source_title,0,$i));
  }
  $i=strpos($source_title,"|");
  if ($i!==False)
  {
    $source_title=trim(substr($source_title,0,$i));
  }
  $i=strpos($source_title," - ");
  if ($i!==False)
  {
    $source_title=trim(substr($source_title,0,$i));
  }
  $i=strpos($source_title," : ");
  if ($i!==False)
  {
    $source_title=trim(substr($source_title,0,$i));
  }
  $i=strpos($source_title," — ");
  if ($i!==False)
  {
    $source_title=trim(substr($source_title,0,$i));
  }
  $i=strpos($source_title," • ");
  if ($i!==False)
  {
    $source_title=trim(substr($source_title,0,$i));
  }
  privmsg("attempting to submit story: $source_title");
  $port=443;
  $uri="/submit.pl";
  $response=wget($host,$uri,$port,ICEWEASEL_UA);
  $html=strip_headers($response);
  $reskey=extract_text($html,"<input type=\"hidden\" id=\"reskey\" name=\"reskey\" value=\"","\">");
  if ($reskey===False)
  {
    privmsg("error: unable to extract reskey");
    return;
  }
  sleep(25);
  $params=array();
  $params["reskey"]=$reskey;
  $params["name"]=get_bot_nick();
  $params["email"]="";
  $params["subj"]=trim(substr($source_title,0,100));
  $params["primaryskid"]="1";
  $params["tid"]="6";
  $params["sub_type"]="plain";
  $params["story"]=$data."\n\n-- submitted from IRC";
  $params["op"]="SubmitStory";
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
  $html=strip_headers($response);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html);
  $html=clean_text($html);
  var_dump($html);
  if (strpos($html,"Perhaps you would like to enter an email address or a URL next time.")!==False)
  {
    privmsg("submission successful - https://$host/submit.pl?op=list");
  }
  else
  {
    privmsg("error: something went wrong with your submission");
    return;
  }
  unlink($story_filename);
  refresh_list();
}

#####################################################################################################

?>
