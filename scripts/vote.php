<?php

# gpl2
# by crutchy

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

switch ($id)
{
  case "list":
    if (count($data)==0)
    {
      privmsg("  no polls registered");
    }
    else
    {
      foreach ($data as $vote_id => $vote_data)
      {
        privmsg("  ".$vote_id);
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
    if (($id=="register") or ($id=="list"))
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
      $data[$id]["accounts"]=array();
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
        $result=0;
        foreach ($data[$id]["accounts"] as $account => $value)
        {
          $result=$result+$value;
        }
        privmsg("  tally of votes for poll \"$id\" => $result");
        return;
      case "breakdown":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a poll admin may output the vote breakdown for a poll");
          return;
        }
        foreach ($data[$id]["accounts"] as $account => $value)
        {
          privmsg("  $account => $value");
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
      case "+":
      case "up":
        if ($data[$id]["status"]<>"open")
        {
          privmsg("  poll \"$id\" is not currently open for voting");
          return;
        }
        $account=users_get_account($nick);
        $data[$id]["accounts"][$account]=1;
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  upvote registered for poll \"$id\" by account \"$account\"");
        return;
      case "-":
      case "down":
        if ($data[$id]["status"]<>"open")
        {
          privmsg("  poll \"$id\" is not currently open for voting");
          return;
        }
        $account=users_get_account($nick);
        $data[$id]["accounts"][$account]=-1;
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  downvote registered for poll \"$id\" by account \"$account\"");
        return;
    }
  }
}

#####################################################################################################

?>
