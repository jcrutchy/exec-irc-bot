<?php

#####################################################################################################

function utils__format_story($story,$story_template)
{
  if ($story["comments"]==NULL)
  {
    $story["comments"]=0;
  }
  if ($story["score"]==NULL)
  {
    $story["score"]=0;
  }
  $story_html=$story_template;
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

function utils__format_comment($comment,$comment_template,&$comments)
{
  if ($story["score"]==NULL)
  {
    $story["score"]=0;
  }
  $comment_html=$story_template;
  $comment_html=str_replace("%%sid%%",$comment["sid"],$comment_html);
  $comment_html=str_replace("%%cid%%",$comment["cid"],$comment_html);
  $comment_html=str_replace("%%date%%",$comment["timestamp"],$comment_html);
  $score_caption=$comment["score"];
  if ($story["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $comment_html=str_replace("%%score%%",$score_caption,$comment_html);
  $comment_html=str_replace("%%subject%%",$comment["subject"],$comment_html);
  $comment_html=str_replace("%%content%%",$comment["content"],$comment_html);
  $children_html="";
  for ($i=0;$i<count($comments);$i++)
  {
    if ($comments[$i]["parent_cid"]==$comment["cid"])
    {
      $children_html=$children_html.utils__format_comment($comment,$comment_template,$comments);
    }
  }
  $comment_html=str_replace("%%comment_children%%",$children_html,$comment_html);
  return $comment_html;
}

#####################################################################################################

?>
