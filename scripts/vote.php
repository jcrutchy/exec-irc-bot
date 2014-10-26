<?php

# gpl2
# by crutchy

/*

# http://wiki.soylentnews.org/wiki/IRC:exec#Proposed_IRC_voting_system

    user connects to Soylent IRC and identifies with NickServ
    user can get list of available polls using ~vote list (system will allow multiple concurrent polls)
    optional time limit or due time for voting
    user can get list of available preferences for a given vote id using ~vote list <vote_id>
    user can get help on voting using ~vote, ~vote help, ~vote-help or ~vote ?
    user registers to vote using ~vote register <vote_id> <email_address>
    email address must be unique per vote
    bot emails a vote key, which is only good for that user and that vote (key will be a short unique string of random characters, eg: Ar7u2y6T5koBW)
    user votes using /msg exec ~vote <vote_id> <key> <preference>
    if flag set by vote admin, users can suggest vote preference using /msg exec ~vote suggest <vote_id> <key> <preference>
    creating polls by authorized staff/admins to be done via IRC commands
    administrator can optionally set a flag to enable or disable multiple use of same key, and whether new votes replace old votes or cumulate
    adminstrator can see results with ~vote results <vote_id>, or publish to channel with ~vote publish <vote_id>
    bot uses secure connection to IRC, but emailing of keys will be in plain text
    if necessary, possibly ban use of some web email hosts such as hotmail, yahoo, gmail, etc

*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));

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
      privmsg("  no votes registered");
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
      privmsg("  you must specify a vote id");
    }
    if (($id=="register") or ($id=="list"))
    {
      privmsg("  invalid vote id");
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
      privmsg("  vote \"$id\" registered");
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
          privmsg("  only the vote founder can unregister the vote");
          return;
        }
        unset($data[$id]);
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  vote \"$id\" unregistered");
        return;
      case "result":
        $result=0;
        foreach ($data[$id]["accounts"] as $account => $value)
        {
          $result=$result+$value;
        }
        privmsg("  tally of votes for \"$id\" => $result");
        return;
      case "breakdown":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a vote admin may output vote breakdown");
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
          privmsg("  only the vote founder can add vote admins");
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
          privmsg("  account \"$admin_account\" added as admin for vote \"$id\"");
        }
        else
        {
          privmsg("  admin \"$admin_account\" already exists for vote \"$id\"");
        }
        return;
      case "del-admin":
        $account=users_get_account($nick);
        if ($data[$id]["founder"]<>$account)
        {
          privmsg("  only the vote founder can delete vote admins");
          return;
        }
        $admin_account=$parts[0];
        $index=array_search($admin_account,$data[$id]["admins"]);
        if ($index!==False)
        {
          unset($data[$id]["admins"][$index]);
          $data[$id]["admins"]=array_values($data[$id]["admins"]);
          set_array_bucket($data,"<<IRC_VOTE_DATA>>");
          privmsg("  account \"$admin_account\" deleted froms admins for vote \"$id\"");
        }
        else
        {
          privmsg("  admin \"$admin_account\" not found in admins for vote \"$id\"");
        }
        return;
      case "open":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a vote admin may open the vote");
          return;
        }
        $data[$id]["status"]="open";
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  vote \"$id\" opened");
        return;
      case "close":
        $account=users_get_account($nick);
        if (in_array($account,$data[$id]["admins"])==False)
        {
          privmsg("  only a vote admin may close the vote");
          return;
        }
        $data[$id]["status"]="closed";
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  vote \"$id\" closed");
        return;
      case "+":
      case "up":
        if ($data[$id]["status"]<>"open")
        {
          privmsg("  vote \"$id\" not currently open for voting");
          return;
        }
        $account=users_get_account($nick);
        $data[$id]["accounts"][$account]=1;
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  upvote registered for account \"$account\"");
        return;
      case "-":
      case "down":
        if ($data[$id]["status"]<>"open")
        {
          privmsg("  vote \"$id\" not currently open for voting");
          return;
        }
        $account=users_get_account($nick);
        $data[$id]["accounts"][$account]=-1;
        set_array_bucket($data,"<<IRC_VOTE_DATA>>");
        privmsg("  downvote registered for account \"$account\"");
        return;
    }
  }
}

#####################################################################################################

?>
