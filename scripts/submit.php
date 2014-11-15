<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~submit|120|0|0|1|crutchy|||0|php scripts/submit.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

$url=get_redirected_url($trailing);
if ($url===False)
{
  privmsg("error: unable to download source (get_redirected_url)");
  return;
}
$host="";
$uri="";
$port=80;
if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  privmsg("error: unable to download source (get_host_and_uri)");
  return;
}
$response=wget($host,$uri,$port);
$source_html=strip_headers($response);
$source_title=extract_raw_tag($source_html,"title");
$source_title=html_entity_decode($source_title,ENT_QUOTES,"UTF-8");
$source_title=html_entity_decode($source_title,ENT_QUOTES,"UTF-8");

$host="dev.soylentnews.org";
$port=80;
$uri="/submit.pl";
$response=wget($host,$uri,$port,ICEWEASEL_UA);
$html=strip_headers($response);
$reskey=extract_text($html,"<input type=\"hidden\" id=\"reskey\" name=\"reskey\" value=\"","\">");
if ($reskey===False)
{
  privmsg("error: unable to extract reskey (1)");
  return;
}

# <input type="hidden" id="reskey" name="reskey" value="meVCdqpUeZbLO0zfazbV">

$params=array();
$params["reskey"]=$reskey;
$params["name"]=trim(substr($nick,0,50));
$params["email"]="";
$params["subj"]=trim(substr($source_title,0,100));
$params["primaryskid"]="1";
$params["tid"]="6";
$params["sub_type"]="plain";
$params["op"]="PreviewStory";
$params["story"]="stuff stuffity stuff stuff stuff";

$response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
$html=strip_headers($response);

$params["op"]="SubmitStory";
sleep(8);

$response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
$html=strip_headers($response);

var_dump($html);

#####################################################################################################

?>
