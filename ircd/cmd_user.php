<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_user(&$connections,&$nicks,&$channels,&$client,$items)
{
  # USER crutchy crutchy 192.168.0.21 :crutchy
  # USER <username> <hostname> <servername> :<realname>
  $nick=client_nick($connections,$nicks,$client);
  if ($nick===False)
  {
    return;
  }
      if (isset($nicks[$nick]["username"])==True)
      {
        $err="ERROR: USER ALREADY REGISTERED (NUMERIC 462)";
        do_reply($client,$err);
        echo "*** $addr: $err\n";
        break;
      }
      $param_parts=explode(" ",$items["params"]);
      if (count($param_parts)<>3)
      {
        $err="ERROR: INCORRECT NUMBER OF PARAMS (NUMERIC 461)";
        do_reply($client,$err);
        echo "*** $addr: $err\n";
        break;
      }
      $nicks[$nick]["username"]=trim($param_parts[0]);
      $nicks[$nick]["hostname"]=trim($param_parts[1]);
      $nicks[$nick]["servername"]=trim($param_parts[2]);
      $nicks[$nick]["realname"]=trim($items["trailing"]);
      var_dump($nicks);
      echo "*** USER MESSAGE RECEIVED FROM $addr\n";
      break;
    case "JOIN":
      echo "*** JOIN MESSAGE RECEIVED FROM $addr\n";
      $nick=client_nick($connections,$nicks,$client);
      if ($nick===False)
      {
        $err="ERROR: NICK DATA NOT FOUND";
        do_reply($client,$err);
        echo "*** $addr: $err\n";
        break;
      }
      $chan=$items["params"];
      if (isset($channels[$chan])==False)
      {
        $channels[$chan]=array();
        $channels[$chan]["nicks"]=array();
      }
      $channels[$chan]["nicks"][]=$nick;

      break;
    case "QUIT":
      echo "*** QUIT MESSAGE RECEIVED FROM $addr\n";
      break;
    default:
      echo "*** UNKNOWN MESSAGE RECEIVED FROM $addr\n";
  }
}

#####################################################################################################

function parse_data_basic($data)
{
  # :<prefix> <command> <params> :<trailing>
  # the only required part of the message is command
  if ($data=="")
  {
    return False;
  }
  $sub=trim($data,"\n\r\0\x0B");
  $result["microtime"]=microtime(True);
  $result["time"]=date("Y-m-d H:i:s",$result["microtime"]);
  $result["data"]=$sub;
  $result["prefix"]=""; # prefix is optional
  $result["params"]="";
  $result["trailing"]="";
  $result["nick"]="";
  $result["user"]="";
  $result["hostname"]="";
  if (substr($sub,0,1)==":") # prefix found
  {
    $i=strpos($sub," ");
    $result["prefix"]=substr($sub,1,$i-1);
    $sub=substr($sub,$i+1);
  }
  $i=strpos($sub," :");
  if ($i!==False) # trailing found
  {
    $result["trailing"]=substr($sub,$i+2);
    $sub=substr($sub,0,$i);
  }
  $i=strpos($sub," ");
  if ($i!==False) # params found
  {
    $result["params"]=substr($sub,$i+1);
    $sub=substr($sub,0,$i);
  }
  $result["cmd"]=strtoupper($sub);
  if ($result["cmd"]=="")
  {
    return False;
  }
  if ($result["prefix"]<>"")
  {
    # prefix format: nick!user@hostname
    $prefix=$result["prefix"];
    $i=strpos($prefix,"!");
    if ($i===False)
    {
      $result["nick"]=$prefix;
    }
    else
    {
      $result["nick"]=substr($prefix,0,$i);
      $prefix=substr($prefix,$i+1);
      $i=strpos($prefix,"@");
      $result["user"]=substr($prefix,0,$i);
      $prefix=substr($prefix,$i+1);
      $result["hostname"]=$prefix;
    }
  }
  return $result;
}

#####################################################################################################

?>
