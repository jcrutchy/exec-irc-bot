<?php

#####################################################################################################

/*
exec:~slash-test|500|0|0|1|crutchy,Bytram||#dev,#test||php scripts/slash_test.php %%trailing%% %%dest%% %%nick%% %%alias%%
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
  global $passed;
  #comment_test();
  #submit_test();
  $sid="15/05/08/0149218"; # sd-key-sid of testing article
  $path=__DIR__."/slash_tests";
  $filenames=glob($path."/*");
  $test_results=array();
  for ($i=0;$i<count($filenames);$i++)
  {
    if ($i>0)
    {
      sleep(120);
    }
    $filename=$filenames[$i];
    $basename=basename($filename);
    privmsg("*** processing test comment defined in \"".$basename."\"");
    $data=file_get_contents($filename);
    if ($data!==False)
    {
      $keys=array("parent","subject_in","body_in","subject_out","body_out");
      $result=parse_data($keys,$data);
      if ($result===False)
      {
        privmsg("  error parsing data in \"".$basename."\"");
        $passed=False;
        break;
      }
      $parent_cid="";
      if ($result["parent"]<>"")
      {
        if (isset($test_results[$result["parent"]]["cid"])==True)
        {
          $parent_cid=$test_results[$result["parent"]]["cid"];
        }
      }
      $test_results[$basename]=sn_comment($result["subject_in"],$result["body_in"],$sid,$parent_cid);
      if ($test_results[$basename]===False)
      {
        privmsg("  error submitting test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }
      if ($test_results[$basename]["subject"]<>$result["subject_out"])
      {
        privmsg("  subject mismatch for test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }
      if ($test_results[$basename]["body"]<>$result["body_out"])
      {
        privmsg("  body mismatch for test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }
    }
    else
    {
      privmsg("  error reading \"".$basename."\"");
      $passed=False;
      break;
    }
  }
}

#####################################################################################################

function parse_data($keys,$data,$suffix="=")
{
  $result=array();
  $n=count($keys)-1;
  if ($n<0)
  {
    return False;
  }
  for ($i=0;$i<$n;$i++)
  {
    $delim1=$keys[$i].$suffix;
    $delim2=$keys[$i+1].$suffix;
    $result[$keys[$i]]=extract_text($data,$delim1,$delim2);
    if ($result[$keys[$i]]===False)
    {
      return False;
    }
  }
  $delim=$keys[$n].$suffix;
  $result[$keys[$n]]=extract_text($data,$delim,"",True);
  if ($result[$keys[$n]]===False)
  {
    return False;
  }
  return $result;
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
