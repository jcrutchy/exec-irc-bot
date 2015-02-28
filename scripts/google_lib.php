<?php

#####################################################################################################

function google_search($query)
{
  $html=wget("www.google.com.au","/search?source=hp&q=".urlencode($query),80);
  $html=strip_headers($html);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html,"<div>");
  $results=explode("<li class=\"g\">",$html);
  var_dump($results);
  if (count($results)==0)
  {
    return False;
  }
  for ($i=0;$i<count($results);$i++)
  {
    $results[$i]=strip_tags($results[$i]);
  }
  return $results;
}

#####################################################################################################

?>
