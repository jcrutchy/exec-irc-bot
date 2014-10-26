<?php

# gpl2
# by crutchy

# TODO: allow mixed case vote_id and option

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));

if (($trailing=="") or ($trailing=="?") or ($trailing=="help"))
{
  privmsg("  http://sylnt.us/vote");
  return;
}

$parts=explode(" ",$trailing);
delete_empty_elements($parts);

$data=get_array_bucket("<<IRC_VOTE_DATA>>");

$id=filter($parts[0],VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC."_-.");

array_shift($parts);
$trailing=implode(" ",$parts);

$commands=array(
  "list","l",
  "register",
  "unregister",
  "result","r",
  "breakdown","b",
  "add-option","ao",
  "del-option","do",
  "add-admin",
  "del-admin",
  "open",
  "close");

switch ($id)
{
  case "list":
  case "l":
    if (count($data)==0)
    {
      privmsg("  no polls registered");
    }
    else
    {
      if (count($data)==0)
      {
        privmsg("  no polls currently available");
        return;
      }
      foreach ($data as $vote_id => $vote_data)
      {
        $n=count($vote_data["options"]);
        if ($n==0)
        {
          privmsg("  $vote_id [no options]");
          continue;
        }
        else
        {
          privmsg("  $vote_id");
        }
        for ($i=0;$i<$n;$i++)
        {
          if ($i==($n-1))
          {
            privmsg("  └─".$vote_data["options"][$i]);
          }
          else
          {
            privmsg("  ├─".$vote_data["options"][$i]);
          }
        }
      }
    }
    return;
}

if (isset($parts[0])==True)
{
  $action=$parts[0];
  array_shift($parts);
  $trailing=implode(" ",$parts);
  if ($action=="register")
  {
    if ($id=="")
    {
      privmsg("  you must specify a poll id");
    }
    if (in_array($id,$commands)==True)
    {
      privmsg("  invalid poll id");
    }
    else
    {
      $account=users_get_account($nick);
      if ($account=="")
      {
        return;
      }
      $data[$id]=array();
      $data[$id]["founder"]=$account;
      $data[$id]["status"]="closed";
      $data[$id]["options"]=array();
      $data[$id]["votes"]=array();
      $data[$id]["admins"]=array($account);
      set_array_bucket($data,"<<IRC_VOTE_DATA>>");
      privmsg("  poll \"$id\" registered");
    }
  }
  elseif (isset($data[$id])==True)
  {
    switch ($action)
    {
      case "unregister":
        $account=users_get_account($nick);
        if ($data[$id]["founder"]<>$account)
        {
          privmsg("  only the poll founder can unregister the poll");
          return;
        }
        unset($data[$id]);
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  poll \"$id\" unregistered");
        return;
      case "result":
      case "r":
        $n=count($data[$id]["votes"]);
        if ($n==0)
        {
          privmsg("  no votes for poll \"$id\" are registered");
          return;
        }
        $tally=array();
        for ($i=0;$i<count($data[$id]["options"]);$i++)
        {
          $tally[$data[$id]["options"][$i]]=0;
        }
        foreach ($data[$id]["votes"] as $account => $option)
        {
          $tally[$option]=$tally[$option]+1;
        }
        $n=count($tally);
        $i=0;
        foreach ($tally as $option => $result)
        {
          if ($i==($n-1))
          {
            privmsg("  └─$option => $result");
          }
          else
          {
            privmsg("  ├─$option => $result");
          }
          $i++;
        }
        return;
      case "breakdown":
      case "b":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a poll admin may output the vote breakdown for a poll");
          return;
        }
        $n=count($data[$id]["votes"]);
        if ($n==0)
        {
          privmsg("  no votes for poll \"$id\" are registered");
          return;
        }
        $i=0;
        foreach ($data[$id]["votes"] as $account => $option)
        {
          if ($i==($n-1))
          {
            privmsg("  └─$account => $option");
          }
          else
          {
            privmsg("  ├─$account => $option");
          }
          $i++;
        }
        return;
      case "add-option":
      case "ao":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a poll admin may add an option for a poll");
          return;
        }
        $option=$parts[0];
        if (in_array($option,$data[$id]["options"])==False)
        {
          $data[$id]["options"][]=$option;
          set_array_bucket($data,"<<IRC_VOTE_DATA>>");
          privmsg("  option \"$option\" added for poll \"$id\"");
        }
        else
        {
          privmsg("  option \"$option\" already exists for poll \"$id\"");
        }
        return;
      case "del-option":
      case "do":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a poll admin may delete an option for a poll");
          return;
        }
        $option=$parts[0];
        $index=array_search($option,$data[$id]["options"]);
        if ($index!==False)
        {
          unset($data[$id]["options"][$index]);
          $data[$id]["options"]=array_values($data[$id]["options"]);
          set_array_bucket($data,"<<IRC_VOTE_DATA>>");
          privmsg("  option \"$option\" deleted for poll \"$id\"");
        }
        else
        {
          privmsg("  option \"$option\" not found for poll \"$id\"");
        }
        return;
      case "add-admin":
        $account=users_get_account($nick);
        if ($data[$id]["founder"]<>$account)
        {
          privmsg("  only the poll founder can add poll admins");
          return;
        }
        $admin_account=users_get_account($parts[0]);
        if ($admin_account=="")
        {
          privmsg("  invalid admin");
        }
        if (in_array($admin_account,$data[$id]["admins"])==False)
        {
          $data[$id]["admins"][]=$admin_account;
          set_array_bucket($data,"<<IRC_VOTE_DATA>>");
          privmsg("  account \"$admin_account\" added as admin for poll \"$id\"");
        }
        else
        {
          privmsg("  admin \"$admin_account\" already exists for poll \"$id\"");
        }
        return;
      case "del-admin":
        $account=users_get_account($nick);
        if ($data[$id]["founder"]<>$account)
        {
          privmsg("  only the poll founder can delete poll admins");
          return;
        }
        $admin_account=$parts[0];
        $index=array_search($admin_account,$data[$id]["admins"]);
        if ($index!==False)
        {
          unset($data[$id]["admins"][$index]);
          $data[$id]["admins"]=array_values($data[$id]["admins"]);
          set_array_bucket($data,"<<IRC_VOTE_DATA>>");
          privmsg("  account \"$admin_account\" deleted froms admins for poll \"$id\"");
        }
        else
        {
          privmsg("  admin \"$admin_account\" not found in admins for poll \"$id\"");
        }
        return;
      case "open":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a poll admin may open the poll for voting");
          return;
        }
        $data[$id]["status"]="open";
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  poll \"$id\" opened for voting");
        return;
      case "close":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a poll admin may close the poll for voting");
          return;
        }
        $data[$id]["status"]="closed";
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  poll \"$id\" closed for voting");
        return;
      default:
        if ($data[$id]["status"]<>"open")
        {
          privmsg("  poll \"$id\" is not currently open for voting");
          return;
        }
        $account=users_get_account($nick);
        if ($account=="")
        {
          return;
        }
        if (in_array($action,$data[$id]["options"])==False)
        {
          privmsg("  invalid option for poll \"$id\"");
          return;
        }
        $data[$id]["votes"][$account]=$action;
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  vote registered by account \"$account\" with option \"$action\" for poll \"$id\"");
        return;
    }
  }
}

#####################################################################################################

?>
