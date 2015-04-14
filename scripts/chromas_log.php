<?php

#####################################################################################################

/*
exec:~first|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~last|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~random|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~count|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=substr($argv[4],1);

if ($trailing=="debug on")
{
  set_bucket("chromas_irc_log_debug","on");
  privmsg("  enabled debug pm");
  return;
}
elseif ($trailing=="debug off")
{
  unset_bucket("chromas_irc_log_debug");
  privmsg("  disabled debug pm");
  return;
}

$params=parse_parameters($trailing,"="," ");

if ($params!==False)
{
  foreach ($params as $key => $value)
  {
    if (strpos($key," ")!==False)
    {
      $params=False;
      break;
    }
  }
}

if ($params===False)
{
  $response=wget("chromas.0x.no","/s/soylent_log.php",80);
  $html=trim(strip_headers($response));
  $html=str_replace("\n"," ",$html);
  privmsg(chr(3)."03".$html);
  return;
}

# chromas, 23 march '15
if (isset($params['until'])==False)
{
  date_default_timezone_set('UTC');
  $params['until'] = strftime('%F %T', time()-5);
}

/*if (isset($params["channel"])==True)
{
  if ((substr($params["channel"],0,1)<>"#") and (substr($params["channel"],0,1)<>"&"))
  {
    $params["channel"]="#".$params["channel"];
  }
}*/

/*if (isset($params["message"])==True)
{
  $params["message"]=preg_quote($params["message"]);
}*/

$paramstr="";
foreach ($params as $key => $value)
{
  $paramstr=$paramstr."&".urlencode($key)."=".urlencode($value);
}

if (isset($params["channel"])==False)
{
  $paramstr=$paramstr."&channel=".urlencode($dest);
}

$uri="/s/soylent_log.php?op=".$alias.$paramstr;

var_dump($uri);

if (get_bucket("chromas_irc_log_debug")=="on")
{
  pm("chromas","http://chromas.0x.no".$uri);
  pm("crutchy","http://chromas.0x.no".$uri);
}

$response=wget("chromas.0x.no",$uri,80,ICEWEASEL_UA,"",20,"",1024,False);
$html=trim(strip_headers($response));

if ($html=="")
{
  return;
}

$lines=explode("\n",trim($html));

for ($i=0;$i<count($lines);$i++)
{
  $msg=trim($lines[$i]);
  if ($msg<>"")
  {
    privmsg(chr(3)."03".$msg);
  }
}

#####################################################################################################

?>
