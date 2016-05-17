<?php

#####################################################################################################

define("SERVER_BUCKET_INDEX",APP_NAME."_server");

#####################################################################################################

function get_server_bucket()
{
  $server_bucket=get_bucket(SERVER_BUCKET_INDEX);
  if ($server_bucket=="")
  {
    return False;
  }
  $server_bucket=@base64_decode($server_bucket);
  if ($server_bucket===False)
  {
    return False;
  }
  $server_bucket=@unserialize($server_bucket);
  if ($server_bucket===False)
  {
    return False;
  }
  return $server_bucket;
}

#####################################################################################################

function check_server_bucket()
{
  $server_bucket=get_server_bucket();
  if ($server_bucket===False)
  {
    return;
  }
  $used_ports=get_user_localhost_ports(True);
  $found=False;
  for ($i=0;$i<count($used_ports);$i++)
  {
    if (($used_ports[$i]["port"]==$server_bucket["port"]) and ($used_ports[$i]["pid"]==$server_bucket["pid"]))
    {
      $found=True;
      break;
    }
  }
  if ($found==False)
  {
    unset_bucket(SERVER_BUCKET_INDEX);
  }
}

#####################################################################################################

?>
