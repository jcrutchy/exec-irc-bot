<?php

#####################################################################################################

/*
exec:~convert|20|0|0|1|||||php scripts/convert.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

$syntax="syntax: ~convert <amount> <from_unit> <to_unit>";

if ($trailing=="")
{
  convert_privmsg($syntax);
}

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
if (count($parts)<>3)
{
  convert_privmsg($syntax);
}

$amount=$parts[0];
$from_unit=$parts[1];
$to_unit=$parts[2];

$func_name="convert_".strtolower($from_unit)."_".strtolower($to_unit);
if (function_exists($func_name)==True)
{
  call_user_func($func_name,$amount);
}

$func_name="convert_".strtolower($to_unit)."_".strtolower($from_unit);
if (function_exists($func_name)==True)
{
  call_user_func($func_name,$amount,True);
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

function convert_result($amount,$result,$from_unit,$to_unit,$space=True)
{
  if ($space==True)
  {
    convert_privmsg($amount." ".$from_unit." = ".$result." ".$to_unit);
  }
  else
  {
    convert_privmsg($amount.$from_unit." = ".$result.$to_unit);
  }
}

#####################################################################################################

function convert_kg_lb($amount,$reverse=False)
{
  if ($reverse==False)
  {
    convert_result($amount,round($amount*2.20462,3),"kg","lb");
  }
  else
  {
    convert_result($amount,round($amount/2.20462,3),"lb","kg");
  }
}

#####################################################################################################

function convert_in_mm($amount,$reverse=False)
{
  if ($reverse==False)
  {
    convert_result($amount,round($amount*25.4,3),"in","mm");
  }
  else
  {
    convert_result($amount,round($amount/25.4,3),"mm","in");
  }
}

#####################################################################################################

function convert_c_f($amount,$reverse=False)
{
  if ($reverse==False)
  {
    convert_result($amount,round($amount*9/5+32,3),"°C","°F",False);
  }
  else
  {
    convert_result($amount,round(($amount-32)*5/9,3),"°F","°C",False);
  }
}

#####################################################################################################

function convert_c_k($amount,$reverse=False)
{
  if ($reverse==False)
  {
    convert_result($amount,round($amount+273.15,3),"°C"," K",False);
  }
  else
  {
    convert_result($amount,round($amount-273.15,3)," K","°C",False);
  }
}

#####################################################################################################

function convert_kg_shitton($amount,$reverse=False)
{
  if ($reverse==False)
  {
    convert_result($amount,round($amount/42,3),"kg","shitton");
  }
  else
  {
    convert_result($amount,round($amount*42,3),"shitton","kg");
  }
}

#####################################################################################################

function convert_lighthour_m($amount,$reverse=False)
{
  if ($reverse==False)
  {
    convert_result($amount,round($amount*1.079e12,3),"light-hour","m");
  }
  else
  {
    convert_result($amount,round($amount/1.079e12,9),"m","light-hour");
  }
}

#####################################################################################################

?>
