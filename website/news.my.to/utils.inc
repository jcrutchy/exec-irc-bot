<?php

#####################################################################################################

function utils__format_reply_form($sid,&$templates)
{
  $params=array();
  $params["sid"]=$sid;
  if (isset($_GET["c"])==True)
  {
    $params["parent_cid"]=$_GET["c"];
    $params["action"]=template_replace_values($templates["comment_reply_action_c"],$params);
  }
  else
  {
    $params["parent_cid"]=0;
    $params["action"]=template_replace_values($templates["comment_reply_action_s"],$params);
  }
  $params["submit_name"]="preview";
  $params["submit_caption"]="Preview";
  $params["preview"]="";
  $params["nick"]="";
  $params["subject"]="";
  $params["content"]="";
  if ((isset($_POST["preview"])==True) and (isset($_POST["subject"])==True) and (isset($_POST["content"])==True) and (isset($_POST["nick"])==True))
  {
    $params["submit_name"]="submit";
    $params["submit_caption"]="Submit";
    $params["nick"]=$_POST["nick"];
    $params["subject"]=$_POST["subject"];
    $params["content"]=$_POST["content"];
    $params["preview"]=template_replace_values($templates["comment_preview"],$params);
  }
  return template_replace_values($templates["reply_form"],$params);
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
  if ($story["score"]>0)
  {
    $story["score"]="+".$story["score"];
  }
  if ($story["comments"]>1)
  {
    $story["comments"]=$story["comments"]." comments";
  }
  elseif ($story["comments"]>0)
  {
    $story["comments"]=$story["comments"]." comment";
  }
  else
  {
    $story["comments"]="no comments";
  }
  return template_replace_values($templates["story"],$story);
}

#####################################################################################################

function utils__format_comment($comment,&$templates,&$comments)
{
  if ($comment["score"]==NULL)
  {
    $comment["score"]=0;
  }
  if ($comment["score"]>0)
  {
    $comment["score"]="+".$comment["score"];
  }
  $comment["parent_link"]="";
  if ($comment["parent_cid"]>0)
  {
    $comment["parent_link"]=template_replace_values($templates["comment_parent_link"],$comment);
  }
  $comment["children"]="";
  for ($i=0;$i<count($comments);$i++)
  {
    if ($comments[$i]["parent_cid"]==$comment["cid"])
    {
      $comment["children"]=$comment["children"].utils__format_comment($comments[$i],$templates,$comments);
    }
  }
  $comment["comment_children"]="";
  if ($comment["children"]<>"")
  {
    $comment["comment_children"]=template_replace_values($templates["comment_children"],$comment);
  }
  $comment["reply_form"]="";
  if (((isset($_GET["reply"])==True) or (isset($_POST["preview"])==True)) and (isset($_GET["c"])==True))
  {
    if ($_GET["c"]==$comment["cid"])
    {
      $comment["reply_form"]=utils__format_reply_form($comment["sid"],$templates);
      $comment["reply_form"]=template_replace_values($templates["comment_reply_form"],$comment);
    }
  }
  return template_replace_values($templates["comment"],$comment);
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
      $templates[substr($fn,strlen($prefix),strlen($fn)-strlen($prefix)-strlen($suffix))]=trim(file_get_contents($full));
    }
  }
  closedir($handle);
  return $templates;
}

#####################################################################################################

function template_replace_values($template,$params)
{
  foreach ($params as $key => $value)
  {
    $template=str_replace("%%$key%%",$value,$template);
  }
  return $template;
}

#####################################################################################################

?>
