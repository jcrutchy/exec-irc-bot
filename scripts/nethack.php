<?php

#####################################################################################################

/*
#exec:~nethack|15|20|0|1|||||php scripts/nethack.php %%trailing%% %%dest%% %%nick%%
#startup:~join #nethack
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

$fn=DATA_PATH."nethack_ncommander";

$old="";
if (file_exists($fn)==True)
{
  $old=trim(file_get_contents($fn));
}

$host="alt.org";
$port=80;
$uri="/nethack/player-endings.php?player=NCommander";

$response=wget($host,$uri,$port);
$html=strip_headers($response);

$cells=explode("<TR",$html);

$last=array_shift(explode("</TR>",array_pop($cells)));

$last=str_replace("</TD>"," ",$last);
$last=strip_tags($last);

$last=array_pop(explode(">",$last));

$last=clean_text($last);

if ($last<>$old)
{
  pm("#Soylent","NCommander was killed: ".$last);
  file_put_contents($fn,$last);
}
else
{
  term_echo("NETHACK >>> $last");
}

#####################################################################################################

?>
