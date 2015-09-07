<?php

#####################################################################################################

function google_search($query)
{
  $response=wget_ssl("www.google.com.au","/search?source=hp&q=".urlencode($query));
  $html=strip_headers($response);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  $results=explode("<cite class=\"_Rm\">",$html);
  array_shift($results);
  if (count($results)==0)
  {
    return False;
  }
  for ($i=0;$i<count($results);$i++)
  {
    $results[$i]=explode("</cite>",$results[$i])[0];
    $results[$i]=strip_tags($results[$i]);
  }
  return $results;
}

#####################################################################################################

?>
