<?php

#####################################################################################################

function utils__format_story($story,$story_head_template,$story_summary_template)
{
  if ($story["comments"]==NULL)
  {
    $story["comments"]=0;
  }
  if ($story["score"]==NULL)
  {
    $story["score"]=0;
  }
  $story_head=$story_head_template;
  $story_head=str_replace("%%sid%%",$story["sid"],$story_head);
  $story_head=str_replace("%%date%%",$story["timestamp"],$story_head);
  $score_caption=$story["score"];
  if ($story["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $story_head=str_replace("%%score%%",$score_caption,$story_head);
  $comments_caption="no comments";
  if ($story["comments"]>1)
  {
    $comments_caption=$story["comments"]." comments";
  }
  elseif ($story["comments"]>0)
  {
    $comments_caption=$story["comments"]." comment";
  }
  $story_head=str_replace("%%comments%%",$comments_caption,$story_head);
  $story_head=str_replace("%%title%%",$story["title"],$story_head);
  $story_summary=$story_summary_template;
  $story_summary=str_replace("%%summary%%",$story["content"],$story_summary);
  return $story_head.$story_summary;
}

#####################################################################################################

?>
