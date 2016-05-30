<?php

#####################################################################################################

function init_test(&$server_data,$action)
{
  switch ($action)
  {
    case "status":
      $server_data["app_data"]["players"]["john"]=array("kills"=>1,"deaths"=>1,"hostname"=>"john");
      $server_data["app_data"]["players"]["ted"]=array("kills"=>2,"deaths"=>1,"hostname"=>"ted");
      $server_data["app_data"]["players"]["harry"]=array("kills"=>3,"deaths"=>1,"hostname"=>"harry");
      $server_data["app_data"]["players"]["bill"]=array("kills"=>1,"deaths"=>1,"hostname"=>"bill");
      $server_data["app_data"]["players"]["george"]=array("kills"=>6,"deaths"=>1,"hostname"=>"george");
      break;
  }
}

#####################################################################################################

function check_test(&$server_data,$action)
{
  switch ($action)
  {
    case "status":
      $test_keys=array_keys($server_data["app_data"]["players"]);
      $correct_keys=array("george","harry","ted","bill","john");
      if ($test_keys===$correct_keys)
      {
        privmsg("test passed");
      }
      else
      {
        privmsg("test failed");
      }
      break;
  }
}

#####################################################################################################

?>
