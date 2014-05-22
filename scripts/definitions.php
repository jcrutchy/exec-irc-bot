<?php

# gpl2
# by crutchy
# 22-may-2014

$msg=$argv[1];

if (urbandictionary($msg)==False)
{
  if (wolframalpha($msg)==False)
  {
    echo "IRC_MSG $msg: unable to find definition\n";
  }
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

function wolframalpha($msg)
{
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
      return False;
    }
    else
    {
      echo "IRC_MSG [wolframalpha] $msg: $def\n";
      return True;
    }
  }
  else
  { 
    echo "$def\n";
    return False;
  }
}

function urbandictionary($msg)
{
  $html=wget("www.urbandictionary.com","/define.php?term=$msg",80);
  $delim1="<meta content='";
  $delim2="' name='Description' property='og:description'>";
  $i=strpos($html,$delim2);
  $html=substr($html,0,$i);
  $def="";
  for ($j=$i;$j>0;$j--)
  {
    if (substr($html,$j,strlen($delim1))==$delim1)
    {
      $def=trim(substr($html,$j+strlen($delim1)));
      break;
    }
  }
  if (strlen($def)<700)
  {
    if ($def=="")
    {
      return False;
    }
    else
    {
      echo "IRC_MSG [urbandictionary] $msg: $def\n";
      return True;
    }
  }
  else
  { 
    echo "$def\n";
    return False;
  }
}

?>
