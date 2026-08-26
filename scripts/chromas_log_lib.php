<?php

#####################################################################################################

function chromas_log($alias,$trailing,$dest)
{
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
    term_echo("chromas_log failed: invalid parameters");
    return False;
  }
  
  # chromas, 23 march 2015
  if (isset($params["until"])==False)
  {
    date_default_timezone_set("UTC");
    $params["until"] = strftime("%F %T",time()-5);
  }
  
  $paramstr="";
  foreach ($params as $key => $value)
  {
    if ($paramstr<>"")
    {
      $paramstr=$paramstr."&";
    }
    $paramstr=$paramstr.urlencode($key)."=".urlencode($value);
  }
  if (isset($params["channel"])==False)
  {
    $paramstr=$paramstr."&channel=".urlencode($dest);
  }
  if (isset($params["out"])==False)
  {
    $paramstr=$paramstr."&out=irc-full";
  }
  if ($alias=="~log")
  {
    $uri="/s/soylent_log.php?".$paramstr;
  }
  else
  {
    $uri="/s/soylent_log.php?op=".$alias."&".$paramstr;
  }
  if (get_bucket("chromas_irc_log_debug")=="on")
  {
    pm("chromas","http://chromas.0x.no".$uri);
    pm("crutchy","http://chromas.0x.no".$uri);
  }
  $response=wget("chromas.0x.no",$uri,80,ICEWEASEL_UA,"",10,"",1024,False);
  $html=trim(strip_headers($response));
  if ($html=="")
  {
    pm("#","chromas_log failed: no response");
    return False;
  }
  $lines=explode("\n",trim($html));
  return $lines;
}

#####################################################################################################

?>