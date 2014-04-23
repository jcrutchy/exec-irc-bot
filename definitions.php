<?php

# gpl2
# by crutchy
# 23-april-2014

$msg=$argv[1];

$html=wget("www.wolframalpha.com","/input/?i=define%3A$msg",80);

$delim1="context.jsonArray.popups.pod_0200.push( {\"stringified\": \"";
$delim2="\",\"mInput\": \"\",\"mOutput\": \"\", \"popLinks\": {} });";

$i=strpos($html,$delim1)+strlen($delim1);

$html=substr($html,$i);

$i=strpos($html,$delim2);

$def=trim(substr($html,0,$i));

if (strlen($def)<700)
{
  if ($def=="")
  {
    echo "IRC_MSG $msg: unable to find definition\n";
  }
  else
  {
    echo "IRC_MSG $msg: $def\n";
  }
}
else
{ 
  echo "$def\n";
}

function wget($host,$uri,$port)
{
  $fp=fsockopen($host,$port);
  if ($fp===False)
  {
    term_echo("Error connecting to \"$host\".");
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
