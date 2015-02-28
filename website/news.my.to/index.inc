<?php

#####################################################################################################

require_once(__DIR__."/db.inc");
require_once(__DIR__."/utils.inc");

define("TEMPLATES_PATH",__DIR__."/templates/");

$templates=utils__load_templates(TEMPLATES_PATH);

$params["css"]=trim(file_get_contents(__DIR__."/default.css"));
$params["menu"]=utils__format_menu();

$params["body"]="%%body_home%%";

/*if (isset($_GET["sid"])==True)
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
}*/

echo utils__template_replace("page",$params);

#####################################################################################################

?>
