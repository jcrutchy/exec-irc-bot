<?php

#####################################################################################################

function title_privmsg($trailing,$channel,$show_rd)
{
  $list_http=explode("http://",$trailing);
  array_shift($list_http);
  for ($i=0;$i<count($list_http);$i++)
  {
    $parts=explode(" ",$list_http[$i]);
    $list_http[$i]="http://".$parts[0];
    if (substr($list_http[$i],0,7)<>"http://")
    {
      unset($list_http[$i]);
    }
  }
  $list_http=array_values($list_http);
  $list_https=explode("https://",$trailing);
  array_shift($list_https);
  for ($i=0;$i<count($list_https);$i++)
  {
    $parts=explode(" ",$list_https[$i]);
    $list_https[$i]="https://".$parts[0];
    if (substr($list_https[$i],0,8)<>"https://")
    {
      unset($list_https[$i]);
    }
  }
  $list_https=array_values($list_https);
  $list=array_merge($list_http,$list_https);
  $out=array();
  for ($i=0;$i<min(4,count($list));$i++)
  {
    $redirect_data=get_redirected_url($list[$i],"","",array());
    if ($redirect_data===False)
    {
      continue;
    }
    $rd_url=$redirect_data["url"];
    # INCORPORATED THE FOLLOWING CONDITION TO ACCOMMODATE ohmibod YOUTUBE TITLES
    if ((strpos($rd_url,"youtube")!==False) and ($channel=="##anime-japanese"))
    {
      continue;
    }
    $raw=get_raw_title($redirect_data);
    if ($raw!==False)
    {
      $def=translate("auto","en",$raw);
      $msg=chr(3)."13".$raw.chr(3);
      if (($def<>$raw) and ($def<>""))
      {
        $msg=$msg." [".chr(3)."04".$def.chr(3)."]";
      }
      if (($rd_url<>$list[$i]) and ($show_rd==True))
      {
        $msg=$msg." - ".chr(3)."03".$rd_url;
      }
      $out[]=$msg;
    }
    else
    {
      term_echo("title: get_raw_title returned false");
    }
  }
  $n=count($out);
  if ($n==0)
  {
    term_echo("title: no titles to output");
  }
  for ($i=0;$i<$n;$i++)
  {
    if ($i==($n-1))
    {
      pm($channel,"└─ ".$out[$i]);
    }
    else
    {
      pm($channel,"├─ ".$out[$i]);
    }
  }
}

#####################################################################################################

function get_raw_title($redirect_data)
{
  $rd_url=$redirect_data["url"];
  $rd_cookies=$redirect_data["cookies"];
  $rd_extra_headers=$redirect_data["extra_headers"];
  $host="";
  $uri="";
  $port=80;
  if (get_host_and_uri($rd_url,$host,$uri,$port)==False)
  {
    term_echo("get_host_and_uri=false");
    return False;
  }
  $breakcode="return ((strpos(strtolower(\$response),\"</title>\")!==False) or (strlen(\$response)>=10000));";
  #$breakcode="";
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$rd_extra_headers,20,$breakcode,256);
  #var_dump($response);
  $html=strip_headers($response);
  $title=extract_raw_tag($html,"title");
  $title=html_decode($title);
  $title=trim(html_decode($title));
  if ($title=="")
  {
    term_echo("  get_raw_title: title is empty");
    return False;
  }
  return $title;
}

#####################################################################################################

/*function get_title($url,$title="")
{
  if ($title=="")
  {
    $title=get_raw_title($url);
  }
  if ($title===False)
  {
    return False;
  }
  $filtered_url=strtolower(filter_non_alpha_num($url));
  $filtered_title=strtolower(filter_non_alpha_num($title));
  if ($filtered_title=="")
  {
    term_echo("filtered_title is empty");
    return False;
  }
  term_echo("  filtered_url = $filtered_url");
  term_echo("filtered_title = $filtered_title");
  $original_title=$title;
  if (strpos($filtered_url,$filtered_title)===False)
  {
    $delims=array("--","|"," - "," : "," | "," — "," • ");
    for ($i=0;$i<count($delims);$i++)
    {
      $parts=explode($delims[$i],$title);
      if (count($parts)==2)
      {
        if ($original_title<>$title)
        {
          $title=$original_title;
          break;
        }
        $filtered_left=strtolower(filter_non_alpha_num($parts[0]));
        $filtered_right=strtolower(filter_non_alpha_num($parts[1]));
        if ((strpos($filtered_url,$filtered_left)!==False) and (strpos($filtered_url,$filtered_right)!==False))
        {
          term_echo("portions of title left and right of \"".$delims[$i]."\" exists in url");
          return False;
        }
        if ((strpos($filtered_url,$filtered_left)!==False) and (strpos($filtered_url,$filtered_right)===False))
        {
          term_echo("portion of title left of \"".$delims[$i]."\" exists in url - showing right");
          $title=$parts[1];
        }
        if ((strpos($filtered_url,$filtered_left)===False) and (strpos($filtered_url,$filtered_right)!==False))
        {
          term_echo("portion of title left of \"".$delims[$i]."\" exists in url - showing left");
          $title=$parts[0];
        }
      }
    }
  }
  else
  {
    term_echo("title exists in url");
    return False;
  }
  return $title;
}*/

#####################################################################################################

?>
