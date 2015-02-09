<?php

#####################################################################################################

/*
  RewriteEngine On
  RewriteRule ^/tfa/(.*)$ /index.php?tfa=$1 [L,QSA]
  RewriteRule ^/img/(.*)$ /index.php?img=$1 [L,QSA]
  RewriteRule ^/op/(.*)$ /index.php?op=$1 [L,QSA]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
*/

#####################################################################################################

define("TEMPLATES_PATH",__DIR__."/templates");

$page=trim(file_get_contents(TEMPLATES_PATH."/template_page.txt"));

$css=trim(file_get_contents(__DIR__."/default.css"));
$page=str_replace("%%css%%",$css,$page);

$menu=trim(file_get_contents(TEMPLATES_PATH."/template_menu.txt"));
$menu_item_template=trim(file_get_contents(TEMPLATES_PATH."/template_menu_item.txt"));
$active_menu_item_template=trim(file_get_contents(TEMPLATES_PATH."/template_menu_item_active.txt"));
$menu_items_html="";
$menu_items_data=file_get_contents(__DIR__."/menu_items.txt");
$menu_items_data=explode("\n",$menu_items_data);
$settings=array();
for ($i=0;$i<count($menu_items_data);$i++)
{
  $parts=explode("=",$menu_items_data[$i]);
  $caption=$parts[0];
  array_shift($parts);
  $href=implode("=",$parts);
  if ($href=="")
  {
    $menu_item_html=$caption;
  }
  else
  {
    if ($_SERVER["REQUEST_URI"]==$href)
    {
      $menu_item_html=$active_menu_item_template;
    }
    else
    {
      $menu_item_html=$menu_item_template;
      $menu_item_html=str_replace("%%href%%",$href,$menu_item_html);
    }
    $menu_item_html=str_replace("%%caption%%",$caption,$menu_item_html);
  }
  $menu_items_html=$menu_items_html.$menu_item_html;
}
$menu=str_replace("%%menu_items%%",$menu_items_html,$menu);

$page=str_replace("%%title%%","news.my.to",$page);

$article_head_template=trim(file_get_contents(TEMPLATES_PATH."/template_article_head.txt"));
$article_summary_template=trim(file_get_contents(TEMPLATES_PATH."/template_article_summary.txt"));

$articles_html="";

$articles=array();

$test_article=array();
$test_article["tfa"]=1678;
$test_article["date"]="16 Feb 2015";
$test_article["score"]=5;
$test_article["comments"]=54;
$test_article["title"]="Test article title";
$test_article["summary"]="Google is reportedly laying plans to enter the wireless phone service business, in competition with Verizon and AT&T and in co-opetition with T-Mobile and Sprint. In late January, The Information broke the news that the company had reached agreements with T-Mobile and Sprint (paywalled, but a secondary report is here) enabling Google to use their cellular networks. When placing a call, Google's handset would choose between the two carrier networks, or Wi-Fi, depending on the caller's location and relative signal strength of the alternatives.";

$articles[]=$test_article;
$articles[]=$test_article;
$articles[]=$test_article;
$articles[]=$test_article;
$articles[]=$test_article;
$articles[]=$test_article;

for ($i=0;$i<count($articles);$i++)
{
  $article_head=$article_head_template;
  $article_head=str_replace("%%tfa%%",$articles[$i]["tfa"],$article_head);
  $article_head=str_replace("%%date%%",$articles[$i]["date"],$article_head);
  $score_caption=$articles[$i]["score"];
  if ($articles[$i]["score"]>0)
  {
    $score_caption="+".$score_caption;
  }
  $article_head=str_replace("%%score%%",$score_caption,$article_head);
  $comments_caption="no comments";
  if ($articles[$i]["comments"]>1)
  {
    $comments_caption=$articles[$i]["comments"]." comments";
  }
  elseif ($articles[$i]["comments"]>0)
  {
    $comments_caption=$articles[$i]["comments"]." comment";
  }
  $article_head=str_replace("%%comments%%",$comments_caption,$article_head);
  $article_head=str_replace("%%title%%",$articles[$i]["title"],$article_head);
  $article_summary=$article_summary_template;
  $article_summary=str_replace("%%summary%%",$articles[$i]["summary"],$article_summary);
  $articles_html=$articles_html.$article_head.$article_summary;
}

$body=$menu.$articles_html;

$page=str_replace("%%body%%",$body,$page);

echo $page;

#####################################################################################################

?>
