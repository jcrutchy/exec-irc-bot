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
  global $passed;
  #comment_test();
  #submit_test();
  $sid="15/05/06/1252256"; # sd-key-sid of testing article
  $parser=xml_parser_create();
  $path=__DIR__."/slash_tests";
  $filenames=glob($path."/*");
  $test_results=array();
  for ($i=0;$i<count($filenames);$i++)
  {
    $filename=$filenames[$i];
    $basename=basename($filename);
    privmsg("processing test comment defined in \"".$basename."\"");
    $data=file_get_contents($filename);
    $values=array();
    $index=array();
    $result=xml_parse_into_struct($parser,$data,$values,$index);
    if ($result==1)
    {
      if (isset($index["SUBJECT"])==False)
      {
        if (count($index["SUBJECT"])<>1)
        {
          privmsg("test comment defined in \"".$basename."\" contains no subject (1)");
          $passed=False;
          break;
        }
        if (isset($values[$index["SUBJECT"][0]]["value"])==False)
        {
          privmsg("test comment defined in \"".$basename."\" contains no subject (2)");
          $passed=False;
          break;
        }
      }
      if (isset($index["BODY"])==False)
      {
        if (count($index["BODY"])<>1)
        {
          privmsg("test comment defined in \"".$basename."\" contains no body (1)");
          $passed=False;
          break;
        }
        if (isset($values[$index["BODY"][0]]["value"])==False)
        {
          privmsg("test comment defined in \"".$basename."\" contains no body (2)");
          $passed=False;
          break;
        }
      }
      $subject=$values[$index["SUBJECT"][0]]["value"];
      $comment_body=$values[$index["BODY"][0]]["value"];
      $parent_cid="";
      if (isset($index["PARENT"])==True)
      {
        if (count($index["PARENT"])==1)
        {
          if (isset($values[$index["PARENT"][0]]["value"])==True)
          {
            $parent_cid=$values[$index["PARENT"][0]]["value"];
          }
        }
      }
      $test_results[$basename]=sn_comment($subject,$comment_body,$sid,$parent_cid);
      if ($test_results[$basename]===False)
      {
        privmsg("error submitting test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }
    }
    else
    {
      privmsg("error parsing xml in \"".$basename."\"");
      $passed=False;
      break;
    }
  }
  xml_parser_free($parser);
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
