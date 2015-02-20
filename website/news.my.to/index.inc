<?php

#####################################################################################################

require_once(__DIR__."/db.inc");
require_once(__DIR__."/utils.inc");

define("TEMPLATES_PATH",__DIR__."/templates/");

$templates=load_templates(TEMPLATES_PATH);

$css=trim(file_get_contents(__DIR__."/default.css"));
$menu_items_data=file_get_contents(__DIR__."/menu_items.txt");

$page=$templates["page"];
$page=str_replace("%%css%%",$css,$page);
$menu=$templates["menu"];
$menu_items_html="";
$menu_items_data=explode("\n",$menu_items_data);
$settings=array();
for ($i=0;$i<count($menu_items_data);$i++)
{
  $parts=explode("=",$menu_items_data[$i]);
  $caption=trim($parts[0]);
  array_shift($parts);
  $href=trim(implode("=",$parts));
  if ($href=="")
  {
    $menu_item_html=$caption;
  }
  else
  {
    if ($_SERVER["REQUEST_URI"]==$href)
    {
      $menu_item_html=$templates["menu_item_active"];
    }
    else
    {
      $menu_item_html=$templates["menu_item"];
      $menu_item_html=str_replace("%%href%%",$href,$menu_item_html);
    }
    $menu_item_html=str_replace("%%caption%%",$caption,$menu_item_html);
  }
  $menu_items_html=$menu_items_html.$menu_item_html;
}
$menu=str_replace("%%menu_items%%",$menu_items_html,$menu);
$body=$menu;
if (isset($_GET["sid"])==True)
{
  if (isset($_POST["submit"])==True)
  {
    $message="submitted comment in reply to story ".$_GET["sid"];
  }
  $story=db__get_story($_GET["sid"]);
  $page=str_replace("%%title%%",$story["title"]." - news.my.to",$page);
  $body=$body.utils__format_story($story,$templates);
  $comments_html="";
  $comments=db__get_comments_sid($_GET["sid"]);
  for ($i=0;$i<count($comments);$i++)
  {
    if ($comments[$i]["parent_cid"]==0)
    {
      $comments_html=$comments_html.utils__format_comment($comments[$i],$templates,$comments);
    }
  }
  if (((isset($_GET["reply"])==True) or (isset($_POST["preview"])==True)) and (isset($_GET["c"])==False))
  {
    $body=$body.utils__format_reply_form($_GET["sid"],$templates);
  }
  $comment_container=$templates["comment_container"];
  $comment_container=str_replace("%%comments%%",$comments_html,$comment_container);
  $body=$body.$comment_container;
}
elseif (isset($_GET["cid"])==True)
{
  if (isset($_POST["submit"])==True)
  {
    $message="submitted comment in reply to comment ".$_GET["cid"];
  }
  $comments=db__get_comments_cid($_GET["cid"]);
}
else
{
  $page=str_replace("%%title%%","news.my.to",$page);
  $stories=db__get_all_stories();
  $stories_html="";
  for ($i=0;$i<count($stories);$i++)
  {
    $stories_html=$stories_html.utils__format_story($stories[$i],$templates);
  }
  $body=$body.$stories_html;
}
$uri_parts=parse_url($_SERVER["REQUEST_URI"]);
if (isset($uri_parts["path"])==True)
{
  $body=str_replace("%%url_path%%",$uri_parts["path"],$body);
}
$page=str_replace("%%body%%",$body,$page);
echo $page;

#####################################################################################################

?>
