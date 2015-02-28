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

function utils__format_menu()
{
  $items=file_get_contents(__DIR__."/menu_items.txt");
  $items=explode("\n",$items);
  $params=array();
  $params["menu_items"]="";
  for ($i=0;$i<count($items);$i++)
  {
    $item_params=array();
    $parts=explode("=",$items[$i]);
    $item_params["caption"]=trim($parts[0]);
    array_shift($parts);
    $item_params["href"]=trim(implode("=",$parts));
    if ($item_params["href"]=="")
    {
      $params["menu_items"]=$params["menu_items"].$caption;
    }
    else
    {
      if ($_SERVER["REQUEST_URI"]==$href)
      {
        $menu_item="menu_item_active";
      }
      else
      {
        $menu_item="menu_item";
      }
      $params["menu_items"]=$params["menu_items"].utils__template_replace($menu_item,$item_params);
    }
  }
  return utils__template_replace("menu",$params);
}

#####################################################################################################

function utils__load_templates($path)
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

function utils__template_replace($template_key,$params,$cascade=True)
{
  global $templates;
  if (isset($templates[$template_key])==False)
  {
    return $template_key;
  }
  $result=$templates[$template_key];
  if ($cascade==True)
  {
    do
    {
      $test=utils__template_cascade($result,$params);
    }
    while ($test==True);
  }
  foreach ($params as $key => $value)
  {
    if ($cascade==True)
    {
      do
      {
        $test=utils__template_cascade($value,array());
      }
      while ($test==True);
    }
    $result=str_replace("%%$key%%",$value,$result);
  }
  return $result;
}

#####################################################################################################

function utils__template_cascade(&$template,$params)
{
  global $templates;
  $result=False;
  foreach ($templates as $key => $value)
  {
    if (isset($params[$key])==True)
    {
      continue;
    }
    $i=0;
    $template=str_replace("%%$key%%",$value,$template,$i);
    if ($i>0)
    {
      $result=True;
    }
  }
  return $result;
}

#####################################################################################################

?>
