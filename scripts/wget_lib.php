<?php

#####################################################################################################

function quick_wget($trailing)
{
  $parts=explode(" ",$trailing);
  delete_empty_elements($parts);
  if (count($parts)<2)
  {
    return False;
  }
  $url=$parts[0];
  array_shift($parts);
  $trailing=implode(" ",$parts);
  $parts=explode("<>",$trailing);
  delete_empty_elements($parts);
  if (count($parts)<2)
  {
    return False;
  }
  $delim1=trim($parts[0]);
  $delim2=trim($parts[1]);
  $host="";
  $uri="";
  $port="";
  if (get_host_and_uri($url,$host,$uri,$port)==False)
  {
    return False;
  }
  $response=wget_ssl($host,$uri,$port);
  $result=extract_text($response,$delim1,$delim2);
  if ($result===False)
  {
    return False;
  }
  $result=strip_tags($result);
  $result=html_decode($result);
  $result=html_decode($result);
  $result=trim($result);
  if ($result=="")
  {
    return False;
  }
  return $result;
}

#####################################################################################################

?>
