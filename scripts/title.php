<?php

# gpl2
# by crutchy

/***************************************************************************************************/
/*                                                                                                 */
/*                                     SECURITY VULNERABILITY                                      */
/*                                     ======================                                      */
/*                                                                                                 */
/*  THE BOT CAN BE TRICKED INTO EXECUTING RESTRICTED COMMANDS BY PUTTING THEM IN A PAGE TITLE.     */
/*                                                                                                 */
/*  EXAMPLE: <title>~say test</title>                                                              */
/*                                                                                                 */
/*  THIS CAN BE OVERCOME BY SIMPLY PREFIXING OUTPUT WITH SOMETHING (LIKE "^ " FOR EXAMPLE).        */
/*                                                                                                 */
/***************************************************************************************************/

#####################################################################################################

/*
#exec:~title|30|0|0|0||||0|php scripts/title.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
$trailing=trim($argv[1]);
$url=get_redirected_url($trailing);
if ($url===False)
{
  term_echo("get_redirected_url=false");
  return;
}
$host="";
$uri="";
$port=80;
if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  term_echo("get_host_and_uri=false");
  return;
}
$response=wget($host,$uri,$port);
$html=strip_headers($response);

$title=extract_raw_tag($html,"title");

$title=html_entity_decode($title,ENT_QUOTES,"UTF-8");
$title=html_entity_decode($title,ENT_QUOTES,"UTF-8");

$filtered_url=strtolower(filter_non_alpha_num($url));
$filtered_title=strtolower(filter_non_alpha_num($title));

term_echo("  filtered_url = $filtered_url");
term_echo("filtered_title = $filtered_title");

if (strpos($filtered_url,$filtered_title)===False)
{
  $i=strpos($title," - ");
  if ($i!==False)
  {
    $filtered_title=strtolower(filter_non_alpha_num(substr($title,0,$i)));
    if (strpos($filtered_url,$filtered_title)!==False)
    {
      privmsg("portion of title left of \" - \" exists in url");
      return;
    }
  }
  $i=strpos($title," | ");
  if ($i!==False)
  {
    $filtered_title=strtolower(filter_non_alpha_num(substr($title,0,$i)));
    if (strpos($filtered_url,$filtered_title)!==False)
    {
      privmsg("portion of title left of \" | \" exists in url");
      return;
    }
  }
  privmsg($title);
}
else
{
  privmsg("title exists in url");
}

#####################################################################################################

?>
