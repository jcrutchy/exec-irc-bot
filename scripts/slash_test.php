<?php

#####################################################################################################

/*
exec:~slash-test|90|0|0|1|crutchy,Bytram||#dev,#test||php scripts/slash_test.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

$passed=True;
require_once("lib.php");
require_once("sn_lib.php");

run_all_tests();

if ($passed==True)
{
  privmsg("all tests passed!");
}

#####################################################################################################

function run_all_tests()
{
  comment_test();
  #submit_test();
}

#####################################################################################################

function comment_test()
{
  global $passed;
  $subject="test subject";
  $comment_body="test comment body";
  $sid="15/04/17/1849229"; # sd-key-sid
  $parent_cid="";
  if (sn_comment($subject,$comment_body,$sid,$parent_cid)===False)
  {
    privmsg("comment test failed (1)");
    $passed=False;
  }
}

#####################################################################################################

function submit_test()
{
  global $passed;
  $url="http://phys.org/news348627043.html";
  if (sn_submit($url)==False)
  {
    $passed=False;
  }
}

#####################################################################################################

?>
