<?php

# gpl2
# by crutchy

#####################################################################################################

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
  something_bad_test();
}

#####################################################################################################

function something_bad_test()
{
  global $passed;
  $something_bad=True;
  if ($something_bad==True)
  {
    term_echo("something_bad_test failed! (1)");
    $passed=False;
  }
}

#####################################################################################################

?>
