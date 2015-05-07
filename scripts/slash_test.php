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
    if ($i>0)
    {
      sleep(8);
    }
    $filename=$filenames[$i];
    $basename=basename($filename);
    privmsg("*** processing test comment defined in \"".$basename."\"");
    $data=file_get_contents($filename);
    $values=array();
    $index=array();
    $result=xml_parse_into_struct($parser,$data,$values,$index);
    if ($result==1)
    {
      var_dump($values);
      $subject_in=xml_element($index,$values,"SUBJECT_IN",$basename);
      $body_in=xml_element($index,$values,"BODY_IN",$basename);
      $subject_out=xml_element($index,$values,"SUBJECT_OUT",$basename);
      $body_out=xml_element($index,$values,"BODY_OUT",$basename);
      if (($subject_in===False) or ($body_in===False) or ($subject_out===False) or ($body_out===False))
      {
        break;
      }
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
      /*var_dump($subject_in);
      var_dump($body_in);
      var_dump($subject_out);
      var_dump($body_out);
      var_dump($parent_cid);*/
      /*$test_results[$basename]=sn_comment($subject_in,$body_in,$sid,$parent_cid);
      if ($test_results[$basename]===False)
      {
        privmsg("  error submitting test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }
      if ($test_results[$basename]["subject"]<>$subject_out)
      {
        privmsg("  subject mismatch for test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }
      if ($test_results[$basename]["body"]<>$body_out)
      {
        privmsg("  body mismatch for test comment defined in \"".$basename."\"");
        $passed=False;
        break;
      }*/
    }
    else
    {
      privmsg("  error parsing xml in \"".$basename."\"");
      $passed=False;
      break;
    }
  }
  xml_parser_free($parser);
}

#####################################################################################################

function xml_element($index,$values,$element,$basename)
{
  global $passed;
  if (isset($index[$element])==True)
  {
    /*if (count($index[$element])<>1)
    {
      privmsg("  test comment defined in \"".$basename."\" contains non-unique xml \"".$element."\" index");
      $passed=False;
      return False;
    }*/
    if (isset($values[$index[$element][0]]["value"])==False)
    {
      privmsg("  test comment defined in \"".$basename."\" contains no xml \"".$element."\" value");
      $passed=False;
      return False;
    }
    return $values[$index[$element][0]]["value"];
  }
  else
  {
    privmsg("  test comment defined in \"".$basename."\" contains no xml \"".$element."\" index");
    $passed=False;
    return False;
  }
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
