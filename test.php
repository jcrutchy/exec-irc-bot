<?php

#####################################################################################################

/*
exec:~tests|0|0|0|1|crutchy|||0|php test.php %%trailing%% %%dest%% %%alias%%
exec:~event-test|0|0|0|1|crutchy|||0|php test.php %%trailing%% %%dest%% %%alias%%
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

$trailing=$argv[1];
$dest=$argv[2];
$alias=$argv[3];

if ($alias=="~event-test")
{
  set_bucket(TEST_BUCKET,TEST_VALUE);
  term_echo("*** ~event-test: TEST BUCKET ==> ".get_bucket(TEST_BUCKET));
  return;
}

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
  run_event_registration_test();
  sleep(3);
  run_append_array_bucket_test();
  sleep(3);
  run_array_bucket_element_file_test();
  sleep(3);
  run_user_tracking_test();
}

#####################################################################################################

function run_append_array_bucket_test()
{
  global $passed;
  unset_bucket(TEST_BUCKET);
  $count=20;
  for ($i=0;$i<$count;$i++)
  {
    append_array_bucket(TEST_BUCKET,$i);
  }
  $bucket=get_array_bucket(TEST_BUCKET);
  var_dump($bucket);
  if (count($bucket)<>$count)
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
  unset_bucket(TEST_BUCKET);
  $bucket_index="<<EXEC_EVENT_HANDLERS>>";
  $test_cmd="PRIVMSG";
  $test_data=":%%nick%% INTERNAL %%dest%% :~event-test %%trailing%%";
  $data1=get_bucket($bucket_index);
  if (check_handler($bucket_index,$test_cmd,$test_data)==True)
  {
    term_echo("run_event_registration_test failed! (1)");
    $passed=False;
  }
  register_event_handler($test_cmd,$test_data);
  if (check_handler($bucket_index,$test_cmd,$test_data)==False)
  {
    term_echo("run_event_registration_test failed! (2)");
    $passed=False;
  }
  set_bucket("<<SELF_TRIGGER_EVENTS_FLAG>>","1");
  privmsg("event test message");
  sleep(2);
  $test=get_bucket(TEST_BUCKET);
  term_echo("*** TEST BUCKET => $test");
  if ($test<>TEST_VALUE)
  {
    term_echo("run_event_registration_test failed! (3)");
    $passed=False;
  }
  unset_bucket(TEST_BUCKET);
  unset_bucket("<<SELF_TRIGGER_EVENTS_FLAG>>");
  delete_event_handler($test_cmd,$test_data);
  sleep(2);
  if (check_handler($bucket_index,$test_cmd,$test_data)==True)
  {
    term_echo("run_event_registration_test failed! (4)");
    $passed=False;
  }
  $data2=get_bucket($bucket_index);
  if ($data1<>$data2)
  {
    term_echo("run_event_registration_test failed! (5)");
    $passed=False;
  }
}

#####################################################################################################

function check_handler($bucket_index,$cmd,$data)
{
  $handlers=get_array_bucket($bucket_index);
  for ($i=0;$i<count($handlers);$i++)
  {
    $handler=unserialize($handlers[$i]);
    if (isset($handler[$cmd])==True)
    {
      if ($handler[$cmd]==$data)
      {
        return True;
      }
    }
  }
  return False;
}

#####################################################################################################

function run_user_tracking_test()
{
  # TODO
}

#####################################################################################################

?>