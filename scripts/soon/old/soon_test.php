<?php

#####################################################################################################

function run_tests()
{
  $pseudo_code="loop 3 sayhello sayhello";
  $key="loop n code";
  $value="for (\$i=1;\$i<=n;\$i++) { code }";
  $test_map=map_translation($pseudo_code,$key,$value);
  var_dump($test_map);

  /*$pseudo_code="msg hello";
  $key="msg trailing";
  $value="privmsg(trailing);";
  $test_map=map_translation($pseudo_code,$key,$value);
  var_dump($test_map);*/
}

#####################################################################################################

?>
