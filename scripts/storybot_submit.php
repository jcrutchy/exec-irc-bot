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
/*  $response=wget($host,$uri,$port);

  $host="";
  $uri="";
  $port=80;
  if (get_host_and_uri($url,$host,$uri,$port)==False)
  {
    privmsg("error: unable to download source (get_host_and_uri)");
    return False;
  }
  $response=wget($host,$uri,$port);
  if (get_host_and_uri($url,$host,$uri,$port)==False)
  {
    privmsg("error: unable to download source (wget)");
    return False;
  }
  $source_html=strip_headers($response);
  $source_title=extract_raw_tag($source_html,"title");
  $delimiters=array("--","|"," - "," : "," — "," • ");
  for ($i=0;$i<count($delimiters);$i++)
  {
    $j=strpos($source_title,$delimiters[$i]);
    if ($j!==False)
    {
      $source_title=trim(substr($source_title,0,$j));
    }
  }
  if (($source_title===False) or ($source_title==""))
  {
    privmsg("error: title not found or empty");
    return False;
  }
  $source_title=html_decode($source_title);
  $source_title=html_decode($source_title);
  $source_body=extract_meta_content($source_html,"description");
  if (($source_body===False) or ($source_body==""))
  {
    $source_body=extract_meta_content($source_html,"og:description","property");
    if (($source_body===False) or ($source_body==""))
    {
      privmsg("error: description meta content not found or empty");
      return False;
    }
  }
  $html=$source_html;
  $article=extract_raw_tag($html,"article");
  if ($article!==False)
  {
    $html=$article;
  }
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  #strip_all_tag($html,"a");
  strip_all_tag($html,"strong");
  $html=strip_tags($html,"<p>");
  $html=lowercase_tags($html);
  $html=explode("<p",$html);
  $source_body=array();
  for ($i=0;$i<count($html);$i++)
  {
    $parts=explode(">",$html[$i]);
    if (count($parts)>=2)
    {
      array_shift($parts);
      $html[$i]=implode(">",$parts);
    }
    $html[$i]=strip_tags($html[$i]);
    $html[$i]=clean_text($html[$i]);
    $host_parts=explode(".",$host);
    for ($j=0;$j<count($host_parts);$j++)
    {
      if (strlen($host_parts[$j])>3)
      {
        if (strpos(strtolower($html[$i]),strtolower($host_parts[$j]))!==False)
        {
          continue 2;
        }
      }
    }
    if (filter($html[$i],"0123456789")<>"")
    {
      continue;
    }
    if (strlen($html[$i])>1)
    {
      if ($html[$i][strlen($html[$i])-1]<>".")
      {
        continue;
      }
      while (True)
      {
        $j=strlen($html[$i])-1;
        if ($j<0)
        {
          break;
        }
        $c=$html[$i][$j];
        if ($c==".")
        {
          break;
        }
        $html[$i]=substr($html[$i],0,$j);
      }
    }
    if (strlen($html[$i])>100)
    {
      $source_body[]=$html[$i];
    }
  }
  $source_body=implode("\n\n",$source_body);
  $source_body=html_decode($source_body);
  $source_body=html_decode($source_body);
  $host="dev.soylentnews.org";
  $port=443;
  $uri="/submit.pl";
  $response=wget($host,$uri,$port,ICEWEASEL_UA);
  $html=strip_headers($response);
  $reskey=extract_text($html,"<input type=\"hidden\" id=\"reskey\" name=\"reskey\" value=\"","\">");
  if ($reskey===False)
  {
    privmsg("error: unable to extract reskey");
    return False;
  }
  sleep(25);
  $params=array();
  $params["reskey"]=$reskey;
  #$params["name"]=trim(substr($nick,0,50));
  $params["name"]=get_bot_nick();
  $params["email"]="";
  $params["subj"]=trim(substr($source_title,0,100));
  $params["primaryskid"]="1";
  $params["tid"]="6";
  $params["sub_type"]="plain";
  $params["story"]=$source_body."\n\n".$url."\n\n-- submitted from IRC";
  $params["op"]="SubmitStory";
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
  $html=strip_headers($response);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html);
  $html=clean_text($html);
  if (strpos($html,"Perhaps you would like to enter an email address or a URL next time. Thanks for the submission.")!==False)
  {
    privmsg("submission successful - https://$host/submit.pl?op=list");
    return True;
  }
  else
  {
    privmsg("error: something went wrong with your submission");
    return False;
  }*/
}

#####################################################################################################

?>
