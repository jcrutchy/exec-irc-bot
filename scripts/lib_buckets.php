<?php

# gpl2
# by crutchy

#####################################################################################################

function register_event_handler($cmd,$data)
{
  $index="<<EXEC_EVENT_HANDLERS>>";
  $value=serialize(array($cmd=>$data));
  append_array_bucket($index,$value);
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
  return $array;
}

#####################################################################################################

function append_array_bucket($index,$value)
{
  echo "/BUCKET_APPEND $index $value\n";
}

#####################################################################################################

function set_array_bucket($array,$bucket,$unset=True)
{
  $bucket_data=serialize($array);
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

function set_bucket($index,$data)
{
  echo "/BUCKET_SET $index $data\n";
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
