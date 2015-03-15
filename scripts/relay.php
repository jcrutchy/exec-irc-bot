<?php

#####################################################################################################

/*
exec:~relay|0|0|0|1|@||||php scripts/relay.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

define("RELAY_HOST","irciv.us.to");
define("RELAY_URI","/?exec");
define("RELAY_PORT","80");
define("EXEC_KEY_FILE","../pwd/exec_key");

define("FILENAME_PREFIX_REQUEST","request__");
define("FILENAME_PREFIX_RESPONSE","response__");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

#####################################################################################################

$key=file_get_contents(EXEC_KEY_FILE);

while (True)
{
  $params=array();
  $params["exec_key"]=$key;
  $response=wpost("irciv.us.to","/?exec&request_id",80,"",$params);
  $request_ids=unserialize(trim(strip_headers($response)));
  for ($i=0;$i<count($request_ids);$i++)
  {
    $id=$request_ids[$i];
    $request_params=array();
    $request_params["exec_key"]=$key;
    $response=wpost("irciv.us.to","/?exec&request_id",80,"",$request_params);
    $content=trim(strip_headers($response));

    output_message(str_replace("\n"," ",$content));

    $data="i farted";

    $response_params=array();
    $response_params["exec_key"]=$key;
    $response_params["request_id"]=$id;
    $response_params["data"]=$data;
    $response=wpost("irciv.us.to","/?exec",80,"",$response_params);
    $content=trim(strip_headers($response));
    output_message($content);

  }
  sleep(20);
}

#####################################################################################################

function output_message($msg)
{
  privmsg($msg);
}

#####################################################################################################

?>
