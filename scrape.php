<?php

# gpl2
# by crutchy
# 29-march-2014

$host="logs.sylnt.us";
$arr=extract_parts(wget("/"),"<a href=\"","\">");
$n=count($arr);
for ($i=0;$i<$n;$i++)
{
  $content=wget("/".$arr[$i]);
  echo $arr[$i]."\r\n";
}

function extract_parts($data,$open,$close)
{
  $result=array();
  $arrL=explode($open,$data);
  $n=count($arrL);
  for ($i=1;$i<$n;$i++)
  {
    $arrR=explode($close,$arrL[$i]);
    if (count($arrR)==2)
    {
      $result[]=$arrR[0];
    }
  }
  return $result;
}

function wget($uri)
{
  global $host;
  $fp=fsockopen($host,80);
  if ($fp===False)
  {
    echo "Error connecting to \"$host\".\r\n";
    return;
  }
  fwrite($fp,"GET $uri HTTP/1.0\r\nHost: $host\r\nConnection: Close\r\n\r\n");
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  return $response;
}

?>
