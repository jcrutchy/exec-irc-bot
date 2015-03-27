<?php

# gpl2
# by crutchy
# 24-may-2014

# irciv_todo.php

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");
$html=wget("wiki.soylentnews.org","/wiki/IRCiv",80);
$delim1="TO DO LIST";
$delim2="<ol>";
$delim3="</ol>";
$delim4="<li>";
$delim5="</li>";
$i=strpos($html,$delim1);
if ($i===False)
{
  privmsg("IRCiv todo: delim1 not found in wiki page source");
  return;
}
$html=substr($html,$i);
$i=strpos($html,$delim2);
if ($i===False)
{
  privmsg("IRCiv todo: delim2 not found in wiki page source");
  return;
}
$html=substr($html,$i+strlen($delim2));
$i=strpos($html,$delim3);
if ($i===False)
{
  privmsg("IRCiv todo: delim3 not found in wiki page source");
  return;
}
$html=substr($html,0,$i);
$i=strpos($html,$delim4);
if ($i===False)
{
  privmsg("IRCiv todo: delim4 not found in wiki page source");
  return;
}
$html=substr($html,$i+strlen($delim4));
$i=strpos($html,$delim5);
if ($i===False)
{
  privmsg("IRCiv todo: delim5 not found in wiki page source");
  return;
}
$html=trim(substr($html,0,$i));
$html=substr($html,0,1000);
if ($html=="")
{
  privmsg("IRCiv todo: not found in wiki page");
}
else
{
  privmsg("IRCiv todo: $html");
}

#####################################################################################################

?>
