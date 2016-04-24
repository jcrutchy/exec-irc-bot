<?php

#####################################################################################################

function exec_alias_config_value($alias,$name)
{
  $exec_list_bucket=get_bucket("<<EXEC_LIST>>");
  if ($exec_list_bucket=="")
  {
    term_echo("*** exec_alias_config_value: error getting exec list bucket");
    return False;
  }
  $exec_list_bucket=base64_decode($exec_list_bucket);
  if ($exec_list_bucket===False)
  {
    term_echo("*** exec_alias_config_value: error decoding exec list bucket");
    return False;
  }
  $exec_list=unserialize($exec_list_bucket);
  if ($exec_list===False)
  {
    term_echo("*** exec_alias_config_value: error unserializing exec list bucket");
    return False;
  }
  if (isset($exec_list[$alias])==False)
  {
    term_echo("*** exec_alias_config_value: alias not found");
    return False;
  }
  if (isset($exec_list[$alias][$name])==False)
  {
    term_echo("*** exec_alias_config_value: name not found");
    return False;
  }
  return $exec_list[$alias][$name];
}

#####################################################################################################

function exec_alias_config_macro($action,$alias,$name,$value="")
{
  if ($value<>"")
  {
    echo "/INTERNAL ~exec-alias-config $action $alias $name $value\n";
    return;
  }
  if ($name<>"")
  {
    echo "/INTERNAL ~exec-alias-config $action $alias $name\n";
    return;
  }
  echo "/INTERNAL ~exec-alias-config $action $alias\n";
}

#####################################################################################################

function save_bucket_to_file($index,$filename)
{
  $bucket=get_array_bucket($index);
  $data=json_encode($bucket,JSON_PRETTY_PRINT);
  file_put_contents(DATA_PATH.$filename,$data);
}

#####################################################################################################

function load_bucket_from_file($index,$filename)
{
  $fn=DATA_PATH.$filename;
  if (file_exists($fn)==False)
  {
    return False;
  }
  $data=file_get_contents($fn);
  $bucket=json_decode($data,True);
  if ($bucket==NULL)
  {
    return False;
  }
  set_array_bucket($bucket,$index,True);
  return $bucket;
}

#####################################################################################################

function save_array_bucket_element_to_file($index,$key,$filename)
{
  $bucket=get_array_bucket($index);
  if (isset($bucket[$key])==False)
  {
    return False;
  }
  $data=json_encode($bucket[$key],JSON_PRETTY_PRINT);
  file_put_contents(DATA_PATH.$filename,$data);
  return True;
}

#####################################################################################################

function load_array_bucket_element_from_file($index,$key,$filename)
{
  $fn=DATA_PATH.$filename;
  if (file_exists($fn)==False)
  {
    return False;
  }
  $data=file_get_contents($fn);
  $element=json_decode($data,True);
  if ($element==NULL)
  {
    return False;
  }
  $bucket=get_array_bucket($index);
  $bucket[$key]=$element;
  set_array_bucket($bucket,$index,True);
  return True;
}

#####################################################################################################

function register_all_events($alias,$privmsg=False)
{
  register_event_handler("JOIN",":".get_bot_nick()." INTERNAL :$alias event-join %%nick%% %%params%%");
  register_event_handler("KICK",":".get_bot_nick()." INTERNAL :$alias event-kick %%params%%");
  register_event_handler("NICK",":".get_bot_nick()." INTERNAL :$alias event-nick %%nick%% %%trailing%%");
  register_event_handler("PART",":".get_bot_nick()." INTERNAL :$alias event-part %%nick%% %%params%%");
  register_event_handler("QUIT",":".get_bot_nick()." INTERNAL :$alias event-quit %%nick%%");
  if ($privmsg==True)
  {
    register_event_handler("PRIVMSG",":".get_bot_nick()." INTERNAL :$alias event-privmsg %%nick%% %%dest%% %%trailing%%");
  }
}

#####################################################################################################

function register_event_handler($cmd,$data)
{
  $cmd=strtoupper(trim($cmd));
  $index="<<EXEC_EVENT_HANDLERS>>";
  $value=serialize(array($cmd=>$data));
  append_array_bucket($index,$value);
  term_echo("REGISTERED EVENT HANDLER: $cmd => \"$data\"");
}

#####################################################################################################

function delete_event_handler($cmd,$data)
{
  echo "/DELETE_HANDLER $cmd=>$data\n";
}

#####################################################################################################

function get_array_bucket($bucket)
{
  $array=array();
  $array_bucket=get_bucket($bucket);
  if ($array_bucket=="")
  {
    term_echo("\"$bucket\" bucket contains no data");
  }
  else
  {
    $array_bucket=base64_decode($array_bucket);
    if ($array_bucket===False)
    {
      term_echo("error decoding \"$bucket\" bucket data");
    }
    else
    {
      $bucket_array=unserialize($array_bucket);
      if ($bucket_array===False)
      {
        term_echo("error unserializing \"$bucket\" bucket data");
      }
      else
      {
        $array=$bucket_array;
      }
    }
  }
  return $array;
}

#####################################################################################################

function append_array_bucket($index,$value)
{
  echo "/BUCKET_APPEND $index $value\n";
}

#####################################################################################################

function set_array_bucket($array,$bucket,$unset=False)
{
  $bucket_data=base64_encode(serialize($array));
  if ($bucket_data===False)
  {
    term_echo("error serializing \"$bucket\" bucket");
  }
  else
  {
    if ($unset==True)
    {
      unset_bucket($bucket);
    }
    set_bucket($bucket,$bucket_data);
  }
}

#####################################################################################################

function bucket_list()
{
  return bucket_read("BUCKET_LIST");
}

#####################################################################################################

function get_bucket($index)
{
  return bucket_read("BUCKET_GET",$index);
}

#####################################################################################################

function bucket_read($cmd,$index="")
{
  if ($index<>"")
  {
    echo "/$cmd $index\n";
  }
  else
  {
    echo "/$cmd\n";
  }
  $f=fopen("php://stdin","r");
  $data="";
  while (True)
  {
    $line=trim(fgets($f));
    if (($line=="") or ($line=="<<EOF>>"))
    {
      break;
    }
    $data=$data.$line;
  }
  if ($data===False)
  {
    err("unable to read bucket data");
  }
  else
  {
    return trim($data);
  }
  fclose($f);
}

#####################################################################################################

function set_bucket($index,$data,$timeout=5e6)
{
  echo "/BUCKET_SET $index $data\n";
  $t=microtime(True);
  do
  {
    usleep(0.05e6);
    $test=get_bucket($index);
    if ((microtime(True)-$t)>$timeout)
    {
      return False;
    }
  }
  while ($test<>$data);
}

#####################################################################################################

function unset_bucket($index,$timeout=5)
{
  $t=microtime(True);
  do
  {
    echo "/BUCKET_UNSET $index\n";
    usleep(0.05e6);
    $test=get_bucket($index);
    if ((microtime(True)-$t)>$timeout)
    {
      return False;
    }
  }
  while ($test<>"");
  return True;
}

#####################################################################################################

?>
