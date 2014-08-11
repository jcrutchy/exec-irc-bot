<?php

# gpl2
# by crutchy
# 12-aug-2014

#####################################################################################################

require_once("scripts/lib.php");
require_once("scripts/weather_lib.php");
require_once("scripts/time_lib.php");
require_once("scripts/switches.php");
require_once("scripts/wiki_lib.php");

define("TEST_BUCKET","<<TEST_BUCKET>>");

$passed=True;

run_all_tests();

if ($passed==True)
{
  privmsg("all tests passed!");
}

#####################################################################################################

function run_all_tests()
{
  run_bucket_list_test();
}

#####################################################################################################

function run_bucket_list_test()
{
  global $passed;
  $count=100;
  set_bucket(TEST_BUCKET,serialize(array(0)));
  for ($i=1;$i<$count;$i++)
  {
    append_array_bucket(TEST_BUCKET,$i);
  }
  if (count(get_array_bucket(TEST_BUCKET))<>$count)
  {
    privmsg("run_bucket_list_test failed!");
    $passed=False;
  }
  unset_bucket(TEST_BUCKET);
}

#####################################################################################################

?>
