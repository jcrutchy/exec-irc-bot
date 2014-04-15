<?php

# gpl2
# by crutchy
# 15-april-2014

# 0/0/define/php definitions.php "%%msg%%" "%%chan%%" "%%nick%%"

$msg=$argv[1];

$html=wget("www.wolframalpha.com","/input/?i=define%3A$msg",80);

$delim1="context.jsonArray.popups.pod_0200.push( {\"stringified\": \"";
$delim2="\",\"mInput\": \"\",\"mOutput\": \"\", \"popLinks\": {} });";

$i=strpos($html,$delim1)+strlen($delim1);

$html=substr($html,$i);

$i=strpos($html,$delim2);

$def=trim(substr($html,0,$i));

if (strlen($def)<500)
{
  if ($def=="")
  {
    echo "privmsg unable to find definition\n";
  }
  else
  {
    echo "privmsg $def\n";
  }
}
else
{ 
  echo "$def\n";
}

return;

##################################################################################

# old crap

$chan=$argv[2];
$nick=$argv[3];

$data=file_get_contents("definitions");
$lines=explode("\n",$data);
for ($i=0;$i<count($lines);$i++)
{
  $line=trim($lines[$i]);
  if ($line=="")
  {
    continue;
  }
  if (substr($line,0,1)=="#")
  {
    continue;
  }
  $parts=explode("|",$line);
  if (count($parts)<>2)
  {
    continue;
  }
  if (strtolower($msg)<>strtolower($parts[0]))
  {
    continue;
  }
  $definition=$parts[1];
  echo "privmsg $definition\n";
  return;
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
