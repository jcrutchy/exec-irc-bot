<?php

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

#####################################################################################################

function check_copyright($html)
{
  $html_lower=strtolower($html);
  $html_work=$html;
  $i=0;
  while ($i<1)
  {
    $anchor_url="";
    $html_work=extract_first_anchor_url($html,$anchor_url,True);
    if ($html_work===False)
    {
      continue;
    }
    if (check_url($html,$anchor_url)==False)
    {
      return $anchor_url;
    }
    $i++;
  }
  return False;
}

#####################################################################################################

function extract_first_anchor_url($html,&$anchor_url,$delete=True)
{
  $i=strpos($html,"<a ");
  if ($i===False)
  {
    return False;
  }
  $html=substr($html,$i);
  $i=strpos($html,"href=\"");
  if ($i===False)
  {
    return False;
  }
  $html=substr($html,$i);

  $tok="</a>";
  $i=strpos($html,$tok);
  if ($i===False)
  {
    return False;
  }
  return substr($html,$i+strlen($tok));
}

#####################################################################################################

function copyright_check_url($html,$anchor_url)
{
  $host="";
  $uri="";
  $port="";
  if (get_host_and_uri($anchor_url,$host,$uri,$port)==True)
  {
    $response=wget($host,$uri,$port,ICEWEASEL_UA,"",60);
    $anchor_html=strip_headers($response);
#<crutchy> cull spaces and special chars, lowercase all, strip tags, css etc and compare
#<crutchy> have some kind of arbitrary string length (or maybe %) that causes trigger
  }
  return True;
}

#####################################################################################################

?>
