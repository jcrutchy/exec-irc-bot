<?php

#####################################################################################################

/*
exec:add ~sneak-server
exec:edit ~sneak-server timeout 0
exec:edit ~sneak-server cmd php scripts/sneak/sneak_server.php %%trailing%% %%nick%% %%dest%% %%server%% %%hostname%% %%alias%%
exec:enable ~sneak-server
startup:~join #sneak
*/

/*
Sneak is an irc game where each player aims to increase their kills by moving into the same coordinate as other players.
The sneak server is used to manage a common game data repository, with multiple client processes run by the irc bot all
talking to the server and their requests for modifying game data processed from the queued socket buffers.
*/

#####################################################################################################

define("DATA_PREFIX","sneak");

require_once("data_server.php");

#####################################################################################################

function server_msg_handler(&$server_data,$unpacked,&$response,$channel,$nick,$user,$hostname,$trailing,$trailing_parts,$action)
{
  if ($action<>"admin-init-chan")
  {
    if (isset($server_data["app_data"][$channel])==False)
    {
      server_reply($server_data,$server,$clients,$connections,$client_index,"error: channel not initialized");
      return;
    }
    if (substr($action,0,6)<>"admin-")
    {
      if (isset($server_data["app_data"][$channel]["players"][$hostname])==False)
      {
        init_player($server_data,$channel,$hostname);
      }
    }
  }
  switch ($action)
  {
    case "admin-del-chan":
      if ($server_data["server_admin"]==$hostname)
      {
        if (count($trailing_parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$trailing_parts[0];
        if (isset($server_data["app_data"][$subject])==True)
        {
          unset($server_data["app_data"][$subject]);
          $server_data["app_data_updated"]=True;
          $response["msg"][]="channel \"$subject\" deleted";
        }
        else
        {
          $response["msg"][]="channel \"$subject\" not found";
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "admin-init-chan":
      if ($server_data["server_admin"]==$hostname)
      {
        if (count($trailing_parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$trailing_parts[0];
        if (isset($server_data["app_data"][$subject])==True)
        {
          unset($server_data["app_data"][$subject]);
        }
        init_chan($server_data,$subject);
        $response["msg"][]="channel \"$subject\" initialized";
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-kill":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        if (count($trailing_parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$trailing_parts[0];
        if (isset($server_data["app_data"][$channel]["players"][$subject])==True)
        {
          unset($server_data["app_data"][$channel]["players"][$subject]);
          $server_data["app_data_updated"]=True;
          $response["msg"][]="player \"$subject\" deleted from the game server in this channel";
        }
        else
        {
          $response["msg"][]="player \"$subject\" not found on the game server in this channel";
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-player-data":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        if (count($trailing_parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$trailing_parts[0];
        $user_data=users_get_data($subject);
        if (isset($user_data["hostname"])==False)
        {
          $response["msg"][]="nick \"$subject\" not found";
        }
        else
        {
          $subject=$user_data["hostname"];
          if (isset($server_data["app_data"][$channel]["players"][$subject])==True)
          {
            $output=var_export($server_data["app_data"][$channel]["players"][$subject],True);
            output_ixio_paste($output,False);
            $response["msg"][]="player data for \"$subject\" dumped to http://ix.io/nAz";
          }
          else
          {
           $response["msg"][]="player \"$subject\" not found on the game server in this channel";
          }
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-map":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        $response["msg"][]="i farted";
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-edit-player":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        if (count($trailing_parts)<=3)
        {
          $response["msg"][]="not enough parameters";
          break;
        }
        $subject=array_shift($trailing_parts);
        $user_data=users_get_data($subject);
        if (isset($user_data["hostname"])==False)
        {
          $response["msg"][]="nick \"$subject\" not found";
        }
        else
        {
          $subject=$user_data["hostname"];
          if (isset($server_data["app_data"][$channel]["players"][$subject])==True)
          {
            $data=implode(" ",$trailing_parts);
            $elements=explode("=",$data);
            for ($i=0;$i<count($elements);$i++)
            {
              $elements[$i]=trim($elements[$i]);
            }
            if (count($elements)<2)
            {
              $response["msg"][]="syntax error";
              break;
            }
            $key=array_shift($elements);
            $value=implode("=",$elements);
            $key_elements=explode(" ",$key);
            $data=&$server_data["app_data"][$channel]["players"][$subject];
            for ($i=0;$i<count($key_elements);$i++)
            {
              if (isset($data[$key_elements[$i]])==False)
              {
                $data[$key_elements[$i]]=array();
              }
              $data=&$data[$key_elements[$i]];
            }
            $data=$value;
            unset($data);
            $server_data["app_data_updated"]=True;
            $response["msg"][]="player data for \"$subject\" updated";
          }
          else
          {
           $response["msg"][]="player \"$subject\" not found on the game server in this channel";
          }
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-edit-goody":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        $response["msg"][]="i farted";
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "help":
    case "?":

      break;
    case "player-list":

      break;
    case "chan-list":

      break;
    case "start":

      break;
    case "status":

      break;
    case "die":

      break;
    case "rank":

      break;
    case "l":
    case "left":

      break;
    case "r":
    case "right":

      break;
    case "u":
    case "up":

      break;
    case "d":
    case "down":

      break;
  }
}

#####################################################################################################

function is_gm(&$server_data,$hostname,$channel)
{
  if (isset($server_data["app_data"][$channel]["moderators"])==False)
  {
    return False;
  }
  if ($hostname<>"")
  {
    if (in_array($hostname,$server_data["app_data"][$channel]["moderators"])==True)
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function init_chan(&$server_data,$channel)
{
  $record=array();
  $record["moderators"]=array($server_data["server_admin"]);
  $record["players"]=array();
  $record["goodies"]=array();
  $record["map_size"]=30;
  $server_data["app_data"][$channel]=$record;
  $server_data["app_data_updated"]=True;
}

#####################################################################################################

function init_player(&$server_data,$channel,$hostname)
{
  $record=array();
  $record["hostname"]=$hostname;
  $server_data["app_data"][$channel]["players"][$hostname]=$record;
  $server_data["app_data_updated"]=True;
}

#####################################################################################################

?>
