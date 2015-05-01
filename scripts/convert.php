<?php

#####################################################################################################

/*
exec:~convert|20|0|0|1|||||php scripts/convert.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];

$syntax="syntax: ~convert %amount% %from_unit% %to_unit%";

if ($trailing=="")
{
  convert_privmsg($syntax);
  return;
}

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
if (count($parts)<>3)
{
  convert_privmsg($syntax);
  return;
}

$amount=$parts[0];
$from_unit=$parts[1];
$to_unit=$parts[2];

$func_name="convert_".strtolower($from_unit)."_".strtolower($to_unit);
if (function_exists($func_name)==True)
{
  call_user_func($func_name,$amount,$from_unit,$to_unit);
}

$func_name="convert_".strtolower($to_unit)."_".strtolower($from_unit);
if (function_exists($func_name)==True)
{
  call_user_func($func_name,1/$amount,$to_unit,$from_unit);
}

$response=wget("www.google.com","/finance/converter?a=".$amount."&from=".$from_unit."&to=".$to_unit,80);
$html=strip_headers($response);

$delim1="<div id=currency_converter_result>";
$delim2="</div>";
$result=extract_text_nofalse($html,$delim1,$delim2);
$result=trim(strip_tags($result));

if ($result=="")
{
  convert_privmsg($syntax);
}

convert_privmsg($result);

#####################################################################################################

function convert_privmsg($msg)
{
  privmsg(chr(3)."03".$msg);
  die;
}

#####################################################################################################

function convert_kg_lb($amount,$from_unit,$to_unit)
{
  convert_privmsg($amount." ".$from_unit." = ".round($amount*2.20462,3)." ".$to_unit);
}

#####################################################################################################

function convert_in_mm($amount,$from_unit,$to_unit)
{
  convert_privmsg($amount." ".$from_unit." = ".round($amount*25.4,3)." ".$to_unit);
}

#####################################################################################################

?>
