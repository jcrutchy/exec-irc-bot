<?php

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
  $story_html=$templates["story"];
  $story_html=str_replace("%%sid%%",$story["sid"],$story_html);
  $story_html=str_replace("%%date%%",$story["timestamp"],$story_html);
  $score_caption=$story["score"];
  if ($story["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $story_html=str_replace("%%score%%",$score_caption,$story_html);
  $comments_caption="no comments";
  if ($story["comments"]>1)
  {
    $comments_caption=$story["comments"]." comments";
  }
  elseif ($story["comments"]>0)
  {
    $comments_caption=$story["comments"]." comment";
  }
  $story_html=str_replace("%%comments%%",$comments_caption,$story_html);
  $story_html=str_replace("%%title%%",$story["title"],$story_html);
  $story_html=str_replace("%%summary%%",$story["content"],$story_html);
  return $story_html;
}

#####################################################################################################

function utils__format_comment($comment,&$templates,&$comments)
{
  if ($comment["score"]==NULL)
  {
    $comment["score"]=0;
  }
  $comment_html=$templates["comment"];
  $comment_html=str_replace("%%sid%%",$comment["sid"],$comment_html);
  $comment_html=str_replace("%%cid%%",$comment["cid"],$comment_html);
  $comment_html=str_replace("%%date%%",$comment["timestamp"],$comment_html);
  $score_caption=$comment["score"];
  if ($comment["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $comment_html=str_replace("%%score%%",$score_caption,$comment_html);
  $comment_html=str_replace("%%subject%%",$comment["subject"],$comment_html);
  $comment_html=str_replace("%%content%%",$comment["content"],$comment_html);
  $parent="";
  if ($comment["parent_cid"]>0)
  {
    $parent=$templates["comment_parent_link"];
    $parent=str_replace("%%parent_cid%%",$comment["parent_cid"],$parent);
  }
  $comment_html=str_replace("%%parent_link%%",$parent,$comment_html);
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
  $comment_html=str_replace("%%comment_children%%",$children_html,$comment_html);
  return $comment_html;
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
