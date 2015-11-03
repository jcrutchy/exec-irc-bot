<?php

#####################################################################################################

/*
exec:~perl6|20|0|0|1|||||php scripts/perldoc.php %%trailing%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$alias=trim($argv[2]);

if ($trailing=="")
{
  return;
}

$response=wget("doc.perl6.org","/routine/".urlencode($trailing));
$html=strip_headers($response);

$delim1="<h2 ";
$delim2="</h2>";
$name=extract_text_nofalse($html,$delim1,$delim2);
$name=strip_tags("<".$name);
$name=clean_text($name);
$name=html_decode($name);
$name=html_decode($name);

$delim1="<div class=\"highlight\"><pre>";
$delim2="</pre></div>";
$example=extract_text_nofalse($html,$delim1,$delim2);
$example=strip_tags($example);
$example=clean_text($example);
$example=html_decode($example);
$example=html_decode($example);

$delim1="</pre></div>";
$delim2="</p>";
$description=extract_text_nofalse($html,$delim1,$delim2);
$description=strip_tags($description);
$description=clean_text($description);
$description=html_decode($description);
$description=html_decode($description);

if (($name<>"") and ($example<>"") and ($description<>""))
{
  privmsg(chr(3)."07".$name);
  privmsg(chr(3)."07".$description);
  privmsg(chr(3)."07".$example);
  privmsg(chr(3)."07"."http://doc.perl6.org/routine/".urlencode($trailing));
}
else
{
  privmsg(chr(3)."07"."error/not found");
  privmsg(chr(3)."07"."http://doc.perl6.org/routine/".urlencode($trailing));
}

#####################################################################################################

?>