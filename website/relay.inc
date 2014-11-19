<?php

# gpl2
# by crutchy

#####################################################################################################

define("FILENAME_PREFIX_REQUEST","request__");
define("FILENAME_PREFIX_RESPONSE","response__");
define("FILE_PATH_REQUESTS","../relay/requests");
define("FILE_PATH_RESPONSES","../relay/responses");
define("TOKENS_FILE","/var/include/vhosts/irciv.us.to/relay/tokens");
define("CLIENT_TIMEOUT",60); # seconds

if (isset($_POST["exec_key"])==True)
{
  check_exec_key();
  if ((isset($_POST["request_id"])==True) and (isset($_POST["data"])==True))
  {
    $request_id=trim($_POST["request_id"]);
    $request_filename=FILE_PATH_REQUESTS.FILENAME_PREFIX_REQUEST.$request_id;
    $response_filename=FILE_PATH_RESPONSES.FILENAME_PREFIX_RESPONSE.$request_id;
    if (file_exists($request_filename)==False)
    {
      die("ERROR 01: REQUEST FILE NOT FOUND");
    }
    $data=trim($_POST["data"]);
    if (file_put_contents($response_filename,$data)===False)
    {
      die("ERROR 02: UNABLE TO WRITE RESPONSE FILE");
    }
    if (file_exists($response_filename)==False)
    {
      die("ERROR 03: RESPONSE FILE NOT FOUND");
    }
    die("SUCCESSFULLY CREATED RESPONSE FILE");
  }
  else
  {
    $handle=opendir(FILE_PATH_REQUESTS);
    $n=0;
    $requests=array();
    while (($fn=readdir($handle))!==False)
    {
      if (substr($fn,0,strlen(FILENAME_PREFIX_REQUEST))<>FILENAME_PREFIX_REQUEST)
      {
        continue;
      }
      $filename=FILE_PATH_REQUESTS."/".$fn;
      if (file_exists($filename)==False)
      {
        continue;
      }
      $request_data=file_get_contents($filename);
      if ($request_data===False)
      {
        unlink($filename);
        continue;
      }
      $requests[]=$request_data;
    }
    closedir($handle);
    if (count($requests)==0)
    {
      die("NO REQUESTS");
    }
    die(implode("\n",$requests));
  }
}
else
{
  # client request
  $token=False;
  if (isset($_GET["token"])==True)
  {
    $token=trim($_GET["token"]);
  }
  elseif (isset($_POST["token"])==True)
  {
    $token=trim($_POST["token"]);
  }
  if ($token===False)
  {
    die("ERROR 04: AUTH TOKEN NOT FOUND");
  }
  $username=False;
  if (isset($_GET["username"])==True)
  {
    $username=trim($_GET["username"]);
  }
  elseif (isset($_POST["username"])==True)
  {
    $username=trim($_POST["username"]);
  }
  if ($username===False)
  {
    die("ERROR 05: USERNAME NOT FOUND");
  }
  if (file_exists(TOKENS_FILE)==False)
  {
    die("ERROR 06: SERVER TOKENS FILE NOT FOUND");
  }
  $tokens_file=file_get_contents(TOKENS_FILE);
  if ($tokens_file===False)
  {
    die("ERROR 07: UNABLE TO READ SERVER TOKENS FILE");
  }
  $token_lines=explode("\n",$tokens_file);
  $auth=False;
  for ($i=0;$i<count($token_lines);$i++)
  {
    $token_data=explode(" ",$token_lines[$i]);
    if (count($token_data)<>2)
    {
      continue;
    }
    if (($username===$token_data[0]) and ($token===$token_data[1]))
    {
      $auth=True;
    }
  }
  if ($auth==False)
  {
    die("ERROR 08: AUTHENTICATION FAILED");
  }
  $data="";
  if (isset($_GET["data"])==True)
  {
    $data=trim($_GET["data"]);
  }
  elseif (isset($_POST["data"])==True)
  {
    $data=trim($_POST["data"]);
  }
  if ($data=="")
  {
    die("ERROR 09: NO DATA TO PROCESS");
  }
  $request_id=microtime(True);
  $request_filename=FILE_PATH_REQUESTS.FILENAME_PREFIX_REQUEST.$request_id;
  $response_filename=FILE_PATH_RESPONSES.FILENAME_PREFIX_RESPONSE.$request_id;
  $request_data=array();
  $request_data["request_id"]=$request_id;
  $request_data["request_filename"]=$request_filename;
  $request_data["response_filename"]=$response_filename;
  $request_data["username"]=$username;
  $request_data["token"]=$token;
  $request_data["data"]=$data;
  $request_data["remote_address"]=$_SERVER["REMOTE_ADDR"];
  $request_data["remote_port"]=$_SERVER["REMOTE_PORT"];
  $request_data["request_method"]=$_SERVER["REQUEST_METHOD"];
  $request_data["request_uri"]=$_SERVER["REQUEST_URI"];
  $request_data["referer"]=$_SERVER["HTTP_REFERER"];
  $request_data["user_agent"]=$_SERVER["HTTP_USER_AGENT"];
  $content=serialize($request_data);
  if ($content===False)
  {
    die("ERROR 10: PROBLEM SERIALIZING REQUEST DATA ARRAY");
  }
  if (file_put_contents($request_filename,$content)===False)
  {
    die("ERROR 11: UNABLE TO WRITE REQUEST FILE");
  }
  if (file_exists($request_filename)===False)
  {
    die("ERROR 12: REQUEST FILE NOT FOUND");
  }
  $t=microtime(True);
  $error=False;
  $response_content="";
  while ((microtime(True)-$t)<CLIENT_TIMEOUT)
  {
    decimal_sleep(0.5);
    if (file_exists($response_filename)==False)
    {
      continue;
    }
    $response_content=file_get_contents($response_filename);
    if ($response_content===False)
    {
      $error="ERROR 13: UNABLE TO READ RESPONSE FILE";
      break;
    }
    if (strlen($response_content)==0)
    {
      $error="ERROR 14: EMPTY RESPONSE FILE";
      break;
    }
    break;
  }
  if (file_exists($request_filename)==True)
  {
    unlink($request_filename);
  }
  if (file_exists($response_filename)==True)
  {
    unlink($response_filename);
  }
  if ((file_exists($request_filename)==True) or (file_exists($response_filename)==True))
  {
    die("ERROR 15: UNABLE TO DELETE REQUEST AND/OR RESPONSE FILE");
  }
  if ($error!==False)
  {
    die($error);
  }
  if ($response_content=="")
  {
    die("ERROR 16: REQUEST TIMED OUT");
  }
  die($response_content);
}

#####################################################################################################

function decimal_sleep($seconds)
{
  usleep($seconds*1000000);
}

#####################################################################################################

?>
