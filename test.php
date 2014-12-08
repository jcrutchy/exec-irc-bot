<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~tests|0|0|0|1|crutchy|||0|php test.php
*/

#####################################################################################################

require_once("scripts/lib.php");
require_once("scripts/weather_lib.php");
require_once("scripts/time_lib.php");
require_once("scripts/switches.php");
require_once("scripts/wiki_lib.php");

define("TEST_BUCKET","<<TEST_BUCKET>>");
define("TEST_FILE","test_file");
define("TEST_KEY","test_key");
define("TEST_VALUE","test_value");

$passed=True;

run_all_tests();

if ($passed==True)
{
  privmsg("all tests passed!");
}
else
{
  privmsg("one or more tests failed! (specific errors output to terminal)");
}

#####################################################################################################

function run_all_tests()
{
  run_append_array_bucket_test();
  run_array_bucket_element_file_test();
}

#####################################################################################################

function run_append_array_bucket_test()
{
  global $passed;
  $count=100;
  for ($i=0;$i<$count;$i++)
  {
    append_array_bucket(TEST_BUCKET,$i);
  }
  if (count(get_array_bucket(TEST_BUCKET))<>$count)
  {
    term_echo("run_append_array_bucket_test failed! (1)");
    $passed=False;
  }
  unset_bucket(TEST_BUCKET);
  if (get_bucket(TEST_BUCKET)<>"")
  {
    term_echo("run_append_array_bucket_test failed! (2)");
    $passed=False;
  }
}

#####################################################################################################

function run_array_bucket_element_file_test()
{
  global $passed;
  $test_array=array();
  $test_array[TEST_KEY]=TEST_VALUE;
  set_array_bucket($test_array,TEST_BUCKET,True);
  if (save_array_bucket_element_to_file(TEST_BUCKET,TEST_KEY,TEST_FILE)==False)
  {
    term_echo("run_array_bucket_element_file_test failed! (1)");
    $passed=False;
  }
  if (load_array_bucket_element_from_file(TEST_BUCKET,TEST_KEY,TEST_FILE)==False)
  {
    term_echo("run_array_bucket_element_file_test failed! (2)");
    $passed=False;
  }
  $test_array=array();
  $test_array=get_array_bucket(TEST_BUCKET);
  if (isset($test_array[TEST_KEY])==False)
  {
    term_echo("run_array_bucket_element_file_test failed! (3)");
    $passed=False;
  }
  elseif ($test_array[TEST_KEY]<>TEST_VALUE)
  {
    term_echo("run_array_bucket_element_file_test failed! (4)");
    $passed=False;
  }
  unset_bucket(TEST_BUCKET);
  if (exec_file_delete(TEST_FILE)==False)
  {
    term_echo("run_array_bucket_element_file_test failed! (5)");
    $passed=False;
  }
  if (get_bucket(TEST_BUCKET)<>"")
  {
    term_echo("run_array_bucket_element_file_test failed! (6)");
    $passed=False;
  }
}

#####################################################################################################

function run_event_registration_test()
{
  global $passed;
  $index="<<EXEC_EVENT_HANDLERS>>";
  $test_cmd="PRIVMSG";
  $test_data=":%%nick%% INTERNAL %%dest%% :~test %%trailing%%";
  $data1=get_bucket($index);
  $test_handler_found=False;
  for ($i=0;$i<count($handlers);$i++)
  {
    $handler=unserialize($handlers[$i]);
    if ((count($handler)==1) and (isset($handler[$test_cmd])==$test_data))
    {
      $test_handler_found=True;
      break;
    }
  }
  if ($test_handler_found==True)
  {
    term_echo("run_event_registration_test failed! (1)");
    $passed=False;
  }
  register_event_handler($test_cmd,$test_data);
  $handlers=get_array_bucket($index);
  $test_handler_found=False;
  for ($i=0;$i<count($handlers);$i++)
  {
    $handler=unserialize($handlers[$i]);
    if ((count($handler)==1) and (isset($handler[$test_cmd])==$test_data))
    {
      $test_handler_found=True;
      break;
    }
  }
  if ($test_handler_found==False)
  {
    term_echo("run_event_registration_test failed! (2)");
    $passed=False;
  }
  delete_event_handler($test_cmd,$test_data);
  $data2=get_bucket($index);
  if ($data1<>$data2)
  {
    term_echo("run_event_registration_test failed! (3)");
    $passed=False;
  }
}

#####################################################################################################

?>
