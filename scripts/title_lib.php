<?php

#####################################################################################################

function title_privmsg($trailing,$channel)
{
  $list=explode("http",$trailing);
  array_shift($list);
  for ($i=0;$i<count($list);$i++)
  {
    $parts=explode(" ",$list[$i]);
    $list[$i]="http".$parts[0];
    if ((substr($list[$i],0,7)<>"http://") and (substr($list[$i],0,8)<>"https://"))
    {
      unset($list[$i]);
    }
  }
  $list=array_values($list);
  $out=array();
  for ($i=0;$i<min(4,count($list));$i++)
  {
    $list[$i]=get_title($list[$i]);
    if ($list[$i]!==False)
    {
      $out[]=$list[$i];
    }
  }
  $n=count($out);
  for ($i=0;$i<$n;$i++)
  {
    if ($i==($n-1))
    {
      pm($channel,"  └─ ".$out[$i]);
    }
    else
    {
      pm($channel,"  ├─ ".$out[$i]);
    }
  }
}

#####################################################################################################

function get_title($url,$alias="")
{
  $redirect_data=get_redirected_url($url,"","",array());
  if ($redirect_data===False)
  {
    term_echo("get_redirected_url=false");
    return False;
  }
  $rd_url=$redirect_data["url"];
  $rd_cookies=$redirect_data["cookies"];
  $rd_extra_headers=$redirect_data["extra_headers"];
  term_echo("get_title: ".$rd_url);
  $host="";
  $uri="";
  $port=80;
  if (get_host_and_uri($rd_url,$host,$uri,$port)==False)
  {
    term_echo("get_host_and_uri=false");
    return False;
  }
  if ($alias=="~sizeof")
  {
    $headers=whead($host,$uri,$port);
    $content_length=exec_get_header($headers,"content-length",False);
    if ($content_length<>"")
    {
      if ($content_length>(1024*1024))
      {
        return chr(3)."13".(round($content_length/1024/1024,3))." Mb (header)";
      }
      elseif ($content_length>1024)
      {
        return chr(3)."13".(round($content_length/1024,3))." kb (header)";
      }
      else
      {
        return chr(3)."13".$content_length." bytes (header)";
      }
      return False;
    }
  }
  $breakcode="return (strlen(\$response)>=2000000);";
  if ($alias=="~title")
  {
    $breakcode="return ((strpos(strtolower(\$response),\"</title>\")!==False) or (strlen(\$response)>=10000));";
  }
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$rd_extra_headers,20,$breakcode,256);
  term_echo("*** TITLE => response bytes: ".strlen($response));
  $html=strip_headers($response);
  if ($alias=="~sizeof")
  {
    if ($content_length>(1024*1024))
    {
      return chr(3)."13".(round($content_length/1024/1024,3))." Mb (downloaded)";
    }
    elseif ($content_length>1024)
    {
      return chr(3)."13".(round($content_length/1024,3))." kb (downloaded)";
    }
    else
    {
      return chr(3)."13".$content_length." bytes (downloaded)";
    }
    return False;
  }
  $title=extract_raw_tag($html,"title");
  $title=html_decode($title);
  $title=html_decode($title);
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
  if ($rd_url==$url)
  {
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
  }
  $msg=chr(3)."13".$title;
  if ($rd_url<>$url)
  {
    $msg=$msg.chr(3)." [ ".chr(3)."03".$rd_url.chr(3)." ]";
  }
  return $msg;
}

#####################################################################################################

?>
