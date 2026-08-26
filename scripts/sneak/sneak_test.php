<?php

#####################################################################################################

function init_test(&$server_data,$action,&$unpacked)
{
  switch ($action)
  {
    case "status":
      $server_data["app_data"]["players"]["john"]=array("kills"=>1,"deaths"=>1,"hostname"=>"john","location_x"=>1,"location_y"=>1);
      $server_data["app_data"]["players"]["ted"]=array("kills"=>2,"deaths"=>1,"hostname"=>"ted","location_x"=>2,"location_y"=>1);
      $server_data["app_data"]["players"]["harry"]=array("kills"=>3,"deaths"=>1,"hostname"=>"harry","location_x"=>3,"location_y"=>1);
      $server_data["app_data"]["players"]["bill"]=array("kills"=>1,"deaths"=>1,"hostname"=>"bill","location_x"=>4,"location_y"=>1);
      $server_data["app_data"]["players"]["george"]=array("kills"=>6,"deaths"=>1,"hostname"=>"george","location_x"=>5,"location_y"=>1);
      break;
  }
  $unpacked["hostname"]="ted";
}

#####################################################################################################

function check_test(&$server_data,$action,&$unpacked)
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
