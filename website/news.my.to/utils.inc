<?php

#####################################################################################################

function utils__format_reply_form($sid,&$templates)
{
  $html=$templates["reply_form"];
  $html=str_replace("%%sid%%",$sid,$html);
  $parent_cid=0;
  $anchor="s=$sid#s$sid";
  if (isset($_GET["c"])==True)
  {
    $parent_cid=$_GET["c"];
    $anchor="c=$parent_cid#c$parent_cid";
  }
  $html=str_replace("%%parent_cid%%",$parent_cid,$html);
  $submit_caption="Preview";
  $preview_query="?preview";
  $reply_preview="";
  $nick="";
  $subject="";
  $content="";
  if ((isset($_GET["preview"])==True) and (isset($_POST["reply_subject"])==True) and (isset($_POST["reply_content"])==True) and (isset($_POST["reply_nick"])==True))
  {
    $submit_caption="Submit";
    $preview_query="";
    $comment=array();
    $comment["nick"]=$_POST["reply_nick"];
    $comment["subject"]=$_POST["reply_subject"];
    $comment["content"]=$_POST["reply_content"];
    $reply_preview=utils__format_comment_preview($comment,$templates);
    $nick=$_POST["reply_nick"];
    $subject=$_POST["reply_subject"];
    $content=$_POST["reply_content"];
    $anchor="?".$anchor;
  }
  else
  {
    $anchor="&amp;".$anchor;
  }
  $html=str_replace("%%submit_caption%%",$submit_caption,$html);
  $html=str_replace("%%preview_query%%",$preview_query,$html);
  $html=str_replace("%%reply_preview%%",$reply_preview,$html);
  $html=str_replace("%%nick%%",$nick,$html);
  $html=str_replace("%%subject%%",$subject,$html);
  $html=str_replace("%%content%%",$content,$html);
  $html=str_replace("%%anchor%%",$anchor,$html);
  return $html;
}

#####################################################################################################

function utils__format_story($story,&$templates)
{
  if ($story["comments"]==NULL)
  {
    $story["comments"]=0;
  }
  if ($story["score"]==NULL)
  {
    $story["score"]=0;
  }
  $html=$templates["story"];
  $html=str_replace("%%sid%%",$story["sid"],$html);
  $html=str_replace("%%date%%",$story["timestamp"],$html);
  $score_caption=$story["score"];
  if ($story["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $html=str_replace("%%score%%",$score_caption,$html);
  $comments_caption="no comments";
  if ($story["comments"]>1)
  {
    $comments_caption=$story["comments"]." comments";
  }
  elseif ($story["comments"]>0)
  {
    $comments_caption=$story["comments"]." comment";
  }
  $html=str_replace("%%comments%%",$comments_caption,$html);
  $html=str_replace("%%title%%",$story["title"],$html);
  $html=str_replace("%%summary%%",$story["content"],$html);
  return $html;
}

#####################################################################################################

function utils__format_comment($comment,&$templates,&$comments)
{
  if ($comment["score"]==NULL)
  {
    $comment["score"]=0;
  }
  $html=$templates["comment"];
  $html=str_replace("%%sid%%",$comment["sid"],$html);
  $html=str_replace("%%cid%%",$comment["cid"],$html);
  $html=str_replace("%%date%%",$comment["timestamp"],$html);
  $score_caption=$comment["score"];
  if ($comment["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $html=str_replace("%%score%%",$score_caption,$html);
  $html=str_replace("%%nick%%",$comment["nick"],$html);
  $html=str_replace("%%subject%%",$comment["subject"],$html);
  $html=str_replace("%%content%%",$comment["content"],$html);
  $parent="";
  if ($comment["parent_cid"]>0)
  {
    $parent=$templates["comment_parent_link"];
    $parent=str_replace("%%parent_cid%%",$comment["parent_cid"],$parent);
  }
  $html=str_replace("%%parent_link%%",$parent,$html);
  $children="";
  for ($i=0;$i<count($comments);$i++)
  {
    if ($comments[$i]["parent_cid"]==$comment["cid"])
    {
      $children=$children.utils__format_comment($comments[$i],$templates,$comments);
    }
  }
  $children_html="";
  if ($children<>"")
  {
    $children_html=$templates["comment_children"];
    $children_html=str_replace("%%children%%",$children,$children_html);
  }
  $html=str_replace("%%comment_children%%",$children_html,$html);
  $reply_form_html="";
  if (((isset($_GET["reply"])==True) or (isset($_GET["preview"])==True)) and (isset($_GET["c"])==True))
  {
    if ($_GET["c"]==$comment["cid"])
    {
      $reply_form_html=utils__format_reply_form($comment["sid"],$templates);
      $reply_form_html=str_replace("%%reply_form%%",$reply_form_html,$templates["comment_reply_form"]);
    }
  }
  $html=str_replace("%%reply_form%%",$reply_form_html,$html);
  return $html;
}

#####################################################################################################

function utils__format_comment_preview($comment,&$templates)
{
  $html=$templates["comment_preview"];
  $html=str_replace("%%nick%%",$comment["nick"],$html);
  $html=str_replace("%%subject%%",$comment["subject"],$html);
  $html=str_replace("%%content%%",$comment["content"],$html);
  return $html;
}

#####################################################################################################

function load_templates($path)
{
  $prefix="template_";
  $suffix=".txt";
  $templates=array();
  $handle=opendir($path);
  while (($fn=readdir($handle))!==False)
  {
    $full=$path.$fn;
    if((is_dir($full)==False) and (substr($fn,0,strlen($prefix))==$prefix) and (substr($fn,strlen($fn)-strlen($suffix))==$suffix))
    {
      $templates[substr($fn,strlen($prefix),strlen($fn)-strlen($prefix)-strlen($suffix))]=file_get_contents($full);
    }
  }
  closedir($handle);
  return $templates;
}

#####################################################################################################

?>
