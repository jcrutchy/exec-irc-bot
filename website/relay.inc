<?php

#####################################################################################################

# usage example: http://irciv.us.to/?exec&username=crutchy&token=test&data=fart

#####################################################################################################

define("FILENAME_PREFIX_REQUEST","request__");
define("FILENAME_PREFIX_RESPONSE","response__");
define("FILE_PATH_REQUESTS","/var/include/vhosts/irciv.us.to/relay/requests/");
define("FILE_PATH_RESPONSES","/var/include/vhosts/irciv.us.to/relay/responses/");
define("TOKENS_FILE","/var/include/vhosts/irciv.us.to/relay/tokens");
define("CLIENT_TIMEOUT",60); # seconds

if ((isset($_POST["exec_key"])==True) and (isset($_GET["request_id"])==True))
{
  check_exec_key();
  $request_id=$_GET["request_id"];
  if ($request_id=="")
  {
    # send list of available request id's
    $out=array();
    $handle=opendir(FILE_PATH_REQUESTS);
    while (($fn=readdir($handle))!==False)
    {
      $full=FILE_PATH_REQUESTS."/".$fn;
      $ft=filemtime($full);
      /*if ((microtime(True)-$ft)>1)
      {
        unlink($full);
        continue;
      }*/
      if (substr($fn,0,strlen(FILENAME_PREFIX_REQUEST))==FILENAME_PREFIX_REQUEST)
      {
        $out[]=substr($fn,strlen(FILENAME_PREFIX_REQUEST));
      }
    }
    echo serialize($out);
  }
  else
  {
    # send contents of specific request id
    $request_filename=FILE_PATH_REQUESTS.FILENAME_PREFIX_REQUEST.$request_id;
    if (file_exists($request_filename)==False)
    {
      die("REQUEST FILE NOT FOUND: ".$request_id);
    }
    $content=file_get_contents($request_filename);
    echo $content;
  }
  return;
}

if ((isset($_POST["exec_key"])==True) and (isset($_POST["request_id"])==True) and (isset($_POST["data"])==True))
{
  check_exec_key();
  $request_id=trim($_POST["request_id"]);
  $request_filename=FILE_PATH_REQUESTS.FILENAME_PREFIX_REQUEST.$request_id;
  $response_filename=FILE_PATH_RESPONSES.FILENAME_PREFIX_RESPONSE.$request_id;
  if (file_exists($request_filename)==False)
  {
    die("BOT SCRIPT ERROR 01: REQUEST FILE NOT FOUND");
  }
  $data=trim($_POST["data"]);
  if (file_put_contents($response_filename,$data)===False)
  {
    die("BOT SCRIPT ERROR 02: UNABLE TO WRITE RESPONSE FILE");
  }
  if (file_exists($response_filename)==False)
  {
    die("BOT SCRIPT ERROR 03: RESPONSE FILE NOT FOUND");
  }
  die("BOT SCRIPT SUCCESS: RESPONSE FILE CREATED");
}

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
  die("CLIENT SCRIPT ERROR 01: AUTH TOKEN NOT FOUND");
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
  die("CLIENT SCRIPT ERROR 02: USERNAME NOT FOUND");
}
if (file_exists(TOKENS_FILE)==False)
{
  die("CLIENT SCRIPT ERROR 03: SERVER TOKENS FILE NOT FOUND");
}
$tokens_file=file_get_contents(TOKENS_FILE);
if ($tokens_file===False)
{
  die("CLIENT SCRIPT ERROR 04: UNABLE TO READ SERVER TOKENS FILE");
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
  die("CLIENT SCRIPT ERROR 05: AUTHENTICATION FAILED");
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
  die("CLIENT SCRIPT ERROR 06: NO DATA TO PROCESS");
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
$content=serialize($request_data);
if ($content===False)
{
  die("CLIENT SCRIPT ERROR 07: PROBLEM SERIALIZING REQUEST DATA ARRAY");
}
if (file_put_contents($request_filename,$content)===False)
{
  die("CLIENT SCRIPT ERROR 08: UNABLE TO WRITE REQUEST FILE");
}
if (file_exists($request_filename)===False)
{
  die("CLIENT SCRIPT ERROR 09: REQUEST FILE NOT FOUND");
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
    $error="CLIENT SCRIPT ERROR 10: UNABLE TO READ RESPONSE FILE";
    break;
  }
  if (strlen($response_content)==0)
  {
    $error="CLIENT SCRIPT ERROR 11: EMPTY RESPONSE FILE";
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
  die("CLIENT SCRIPT ERROR 12: UNABLE TO DELETE REQUEST AND/OR RESPONSE FILE");
}
if ($error!==False)
{
  die($error);
}
if ($response_content=="")
{
  die("CLIENT SCRIPT ERROR 13: REQUEST TIMED OUT");
}
die($response_content);

#####################################################################################################

function decimal_sleep($seconds)
{
  usleep($seconds*1000000);
}

#####################################################################################################

?>
