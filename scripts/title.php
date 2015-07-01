<?php

#####################################################################################################

/*
exec:~title-internal|60|0|0|0||INTERNAL|||php scripts/title.php %%trailing%% %%alias%% %%dest%% %%nick%%
exec:~title|60|0|0|0|||||php scripts/title.php %%trailing%% %%alias%% %%dest%% %%nick%%
exec:~sizeof|60|0|0|0|*||#journals,#test,#Soylent,#,#exec,#dev||php scripts/title.php %%trailing%% %%alias%% %%dest%% %%nick%%
init:~title-internal register-events
*/

#####################################################################################################

# TODO: PICK UP ON www. LINKS THAT OMIT A SCHEME PREFIX (ASSUME HTTP)
# TODO: META HTTP-EQUIV=REFRESH URL REDIRECTS

require_once("lib.php");
require_once("title_lib.php");
require_once("translate_lib.php");

$trailing=trim($argv[1]);
$alias=trim($argv[2]);
$dest=$argv[3];
$nick=$argv[4];

$bucket=get_bucket("<exec_title_$dest>");

if ($alias=="~title-internal")
{
  $parts=explode(" ",$trailing);
  $action=strtolower($parts[0]);
  array_shift($parts);
  switch ($action)
  {
    case "register-events":
      register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~title-internal event-privmsg %%nick%% %%dest%% %%trailing%%");
      return;
    case "event-privmsg":
      # trailing = <nick> <channel> <trailing>
      $nick=strtolower($parts[0]);
      $channel=strtolower($parts[1]);
      array_shift($parts);
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
      if ($bucket=="on")
      {
        title_privmsg($trailing,$channel);
      }
      break;
  }
}
elseif ($alias=="~sizeof")
{
  $redirect_data=get_redirected_url($trailing,"","",array());
  if ($redirect_data===False)
  {
    term_echo("get_redirected_url=false");
    return;
  }
  $rd_url=$redirect_data["url"];
  $rd_cookies=$redirect_data["cookies"];
  $rd_extra_headers=$redirect_data["extra_headers"];
  $host="";
  $uri="";
  $port=80;
  if (get_host_and_uri($rd_url,$host,$uri,$port)==False)
  {
    term_echo("get_host_and_uri=false");
    return;
  }
  $headers=whead($host,$uri,$port);
  $content_length=exec_get_header($headers,"content-length",False);
  if ($content_length<>"")
  {
    if ($content_length>(1024*1024))
    {
      privmsg(chr(3)."13".(round($content_length/1024/1024,3))." Mb (header)");
    }
    elseif ($content_length>1024)
    {
      privmsg(chr(3)."13".(round($content_length/1024,3))." kb (header)");
    }
    else
    {
      privmsg(chr(3)."13".$content_length." bytes (header)");
    }
    return;
  }
  $breakcode="return (strlen(\$response)>=2000000);";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$rd_extra_headers,20,$breakcode,256);
  $html=strip_headers($response);
  $content_length=strlen($html);
  if ($content_length>(1024*1024))
  {
    privmsg(chr(3)."13".(round($content_length/1024/1024,3))." Mb (downloaded)");
  }
  elseif ($content_length>1024)
  {
    privmsg(chr(3)."13".(round($content_length/1024,3))." kb (downloaded)");
  }
  else
  {
    privmsg(chr(3)."13".$content_length." bytes (downloaded)");
  }
  return;
}
elseif ($alias=="~title")
{
  if (strtolower($trailing)=="on")
  {
    if ($bucket=="on")
    {
      privmsg("  titles already enabled for ".chr(3)."10$dest");
    }
    else
    {
      set_bucket("<exec_title_$dest>","on");
      privmsg("  titles enabled for ".chr(3)."10$dest");
    }
  }
  elseif (strtolower($trailing)=="off")
  {
    if ($bucket=="")
    {
      privmsg("  titles already disabled for ".chr(3)."10$dest");
    }
    else
    {
      unset_bucket("<exec_title_$dest>");
      privmsg("  titles disabled for ".chr(3)."10$dest");
    }
  }
  else
  {
    $redirect_data=get_redirected_url($trailing,"","",array());
    if ($redirect_data===False)
    {
      term_echo("get_redirected_url=false");
      return;
    }
    $rd_url=$redirect_data["url"];
    $raw=get_raw_title($redirect_data);
    if ($raw!==False)
    {
      $def=translate("auto","en",$raw);
      $msg=chr(3)."13".$raw.chr(3);
      if ($def<>$raw)
      {
        $msg=$msg." [".chr(3)."04".$def.chr(3)."]";
      }
      if ($rd_url<>$trailing)
      {
        $msg=$msg." - ".chr(3)."03".$rd_url;
      }
      privmsg($msg);
    }
  }
}

#####################################################################################################

?>
