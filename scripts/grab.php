<?php

#####################################################################################################

/*

exec:add ~grab
exec:edit ~grab cmd php scripts/grab.php %%trailing%% %%dest%% %%nick%% %%server%% %%alias%% %%timestamp%%
exec:enable ~grab

exec:add ~quote
exec:edit ~quote cmd php scripts/grab.php %%trailing%% %%dest%% %%nick%% %%server%% %%alias%% %%timestamp%%
exec:enable ~quote

*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$server=$argv[4];
$alias=$argv[5];
$timestamp=$argv[6];

$fn=DATA_PATH."quotes_data_".base64_encode($server).".txt";
$data=array();
if (file_exists($fn)==True)
{
  $data=json_decode(file_get_contents($fn),True);
}
if ($alias=="~grab")
{
  $channels=get_array_bucket("<<EXEC_GRAB_CHANNELS_".base64_encode($server).">>");
  switch (strtolower($trailing))
  {
    case "on":
      if (in_array($dest,$channels)==False)
      {
        $channels[]=$dest;
        set_array_bucket($channels,"<<EXEC_GRAB_CHANNELS_".base64_encode($server).">>");
        privmsg("grab enabled for ".chr(3)."10$dest");
        return;
      }
      privmsg("grab already enabled for ".chr(3)."10$dest");
      return;
    case "off":
      $i=array_search($dest,$channels);
      if ($i!==False)
      {
        unset($channels[$i]);
        $channels=array_values($channels);
        set_array_bucket($channels,"<<EXEC_GRAB_CHANNELS_".base64_encode($server).">>");
        privmsg("grab disabled for ".chr(3)."10$dest");
        return;
      }
      privmsg("grab already disabled for ".chr(3)."10$dest");
      return;
  }
  if (in_array($dest,$channels)==False)
  {
    return;
  }
  $quote=get_bucket("last_".strtolower($trailing)."_".strtolower($dest));
  if ($quote=="")
  {
    privmsg("last message by \"$trailing\" not found");
    return;
  }
  $record=array();
  $record["quoted"]=$trailing;
  $record["quoting"]=$nick;
  $record["time"]=$timestamp;
  $record["quote"]=$quote;
  $record["channel"]=$dest;
  $data[]=$record;
  if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
  {
    privmsg("error writing quotes data file");
  }
  else
  {
    privmsg("quote saved: <".chr(3)."05".$record["quoted"]."@".$record["channel"].chr(3)."> ".$record["quote"]);
  }
}
elseif ($alias=="~quote")
{
  if (isset($data[$trailing])==True)
  {
    privmsg("<".chr(3)."05".$record["quoted"]."@".$record["channel"].chr(3)."> ".$record["quote"]);
    return;
  }
  $parts=explode(" ",$trailing);
  if (count($parts)==0)
  {
    return;
  }
  $field=strtolower(array_shift($parts));
  if (count($parts)==0)
  {
    return;
  }
  $query=implode(" ",$parts);
  $fields=array("quoted","quoting","time","quote","channel");
  if (in_array($field,$fields)==False)
  {
    privmsg("invalid query");
    return;
  }
  $results=array();
  for ($i=0;$i<count($data);$i++)
  {
    if (strpos($data[$i][$field],$query)!==False)
    {
      $results[]=$i;
    }
  }
  if (count($results)==0)
  {
    privmsg("no results");
  }
  else
  {
    privmsg("query found in quotes: ".implode(",",$results));
  }
}

#####################################################################################################

?>
