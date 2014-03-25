<?php

# gpl2
# by crutchy
# 25-march-2014

$nick="crutchy_test";
$chan="#test";
$wiki_host="wiki.soylentnews.org";
$wiki_uri="/w/index.php?title=User:Crutchy&amp;action=submit";

set_time_limit(0);
ini_set("display_errors","on");
$joined=0;
$fp=fsockopen("irc.sylnt.us",6667);
fputs($fp,"NICK $nick\r\n");
fputs($fp,"USER $nick * $nick :$nick\r\n");
main();

function main()
{
  global $fp;
  global $nick;
  global $joined;
  global $chan;
  $data=fgets($fp);
  if ($data!==False)
  {
    $parts=explode(" ",$data);
    if (count($parts)>1)
    {
      if ($parts[0]=="PING")
      {
        fputs($fp,"PONG ".$parts[1]."\r\n");
      }
      else
      {
        echo $data;
      }
      if ((trim($parts[1])=="PRIVMSG") and (count($parts)>3))
      {
        $pieces1=explode("!",$parts[0]);
        $pieces2=explode("PRIVMSG $chan :",$data);
        if ((count($pieces1)>1) and (count($pieces2)==2))
        {
          $msg_nick=substr($pieces1[0],1);
          $msg=trim($pieces2[1]);
          if (strlen($msg)>0)
          {
            $i=strpos($msg," ");
            if (($i!==False) and ($msg[0]=="!"))
            {
              $cmd=strtoupper(substr($msg,1,$i-1));
              $content=substr($msg,$i+1);
              switch ($cmd)
              {
                case "WIKI":
                  if (strtoupper(trim($content))=="QUIT")
                  {
                    fputs($fp,":$nick QUIT\r\n");
                    fclose($fp);
                    echo "QUITTING SCRIPT\r\n";
                    return;
                  }
                  fputs($fp,":$nick PRIVMSG $chan :$msg_nick wants to send \"$content\" to the Soylent wiki.\r\n");
                  wiki($msg_nick,$content);
                  break;
              }
            }
          }
        }
      }
    }
    if (($joined==0) and (strpos($data,"End of /MOTD command")!==False))
    {
      $joined=1;
      fputs($fp,"JOIN $chan\r\n");
    }
  }
  main();
}

function wiki($msg_nick,$content)
{
  global $wiki_host;
  global $wiki_uri;
  $wfp=fsockopen($wiki_host,80);
  if ($wfp===False)
  {
    echo "Error connecting to \"$wiki_host\".\r\n";
    return;
  }
  $data="wpSection=".rawurlencode("13");
  $data=$data."&wpTextbox1=".rawurlencode("==IRC BOT TESTING==\r\n$msg_nick: $content");
  $data=$data."&wpSave=".rawurlencode("Save page");
  $request="POST $wiki_uri HTTP/1.0\r\n";
  $request=$request."Host: $wiki_host\r\n";
  $request=$request."Content-Type: application/x-www-form-urlencoded\r\n";
  $request=$request."Content-Length: ".strlen($data)."\r\n";
  $request=$request."Connection: Close\r\n\r\n";
  $request=$request.$data;
  fwrite($wfp,$request);
  $response="";
  while (!feof($wfp))
  {
    $response=$response.fgets($wfp,1024);
  }
  fclose($wfp);
  if (strpos($response,$content)!==False)
  {
    echo "Response contains submitted content :-)\r\n";
  }
  else
  {
    echo "Submitted content not found in response :-(\r\n";
  }
}

?>
