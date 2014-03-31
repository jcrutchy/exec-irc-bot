<?php

# gpl2
# by crutchy
# 31-march-2014

# thanks to mrbluze for his guidance

# note: ping pong can trigger karma upping :-P

# todo: add collective noun substitution
# todoL add verb_from array substitution
# todo: add ability to append arrays from within irc
# todo: use data file instead of arrays (required for dynamic changes)

# your <random word here> are belong to us
# "bacon is ftw" > "your bacon sucks cucumbers" (mrbluze)

# todo: rainbow function (with colored backgrounds) 00,06D00,02I00,12C00,09K00,08S

# http://esl.about.com/library/vocabulary/bl1000_list_noun1.htm

# http://www.mirc.com/colors.html

define("NICK","crunch");
define("CHAN","#test");
define("TRIGGER","~");
define("CMD_COLOR","COLOR");
define("CMD_SUBST","SUBST");
define("CMD_KARMA","KARMA");
define("CMD_ACCOUNT","ACCOUNT");
define("CMD_VOTE","VOTE");
define("TRIGGER_WORD","bacon");
define("ABOUT","\"crunch\" by crutchy: https://github.com/crutchy-/test/blob/master/bacon.php");
set_time_limit(0);
ini_set("display_errors","on");
$joined=0;
$fp=fsockopen("irc.sylnt.us",6667);
fputs($fp,"NICK ".NICK."\r\n");
fputs($fp,"USER ".NICK." * ".NICK." :".NICK."\r\n");
$last="";
$prefix="";
$suffix="";
$color=-1;
$verb_to=array("bonking","trolling","farting","brooming","whacking","slurping","factoring","frogging","spanking");
$noun_from=array("horse","dog","computer","array","table","tabletop","timezone","thing");
$noun_to=array("washing machine","Schrodinger's cat","brown puddle","sticky mess","stool");
$karma="";
$account="";
$karma_delay=0;
while (True)
{
  $data=fgets($fp);
  if ($data===False)
  {
    continue;
  }
  $parts=explode(" ",$data);
  if (count($parts)>1)
  {
    if ($parts[0]=="PING")
    {
      fputs($fp,"PONG ".$parts[1]."\r\n");
    }
    else
    {
      echo $data;
    }
    if (($account<>"") and (strpos(strtoupper($data),strtoupper("330 ".NICK." $account "))!==False) and (strpos($data," :is logged in as")!==False) and (count($parts)>4))
    {
      $account=$account."/".$parts[4];
    }
  }
  $nick="";
  $msg="";
  if (msg_nick($data,$nick,$msg)==True)
  {
    if (strtoupper(substr($msg,0,strlen(TRIGGER)))==TRIGGER)
    {
      $msg=substr($msg,strlen(TRIGGER));
      $cmd_msg="";
      if (strtoupper($msg)=="Q")
      {
        fputs($fp,":".NICK." QUIT\r\n");
        fclose($fp);
        echo "QUITTING SCRIPT\r\n";
        return;
      }
      elseif ($msg=="")
      {
        if ($last<>"")
        {
          $words=explode(" ",$last);
          $j=mt_rand(0,count($words)-1);
          $words[$j]=TRIGGER_WORD;
          privmsg(implode(" ",$words));
        }
        else
        {
          privmsg(ABOUT);
        }
      }
      elseif (iscmd($msg,$cmd_msg,CMD_COLOR)==True)
      {
        if (($cmd_msg>=0) and ($cmd_msg<=15))
        {
          $color=$cmd_msg;
        }
        else
        {
          $color=-1;
        }
      }
      elseif (iscmd($msg,$cmd_msg,CMD_SUBST)==True)
      {
        if ($cmd_msg<>"")
        {
          $subject=$cmd_msg;
        }
      }
      elseif (iscmd($msg,$cmd_msg,CMD_VOTE)==True)
      {
        /*if ($cmd_msg<>"")
        {
          $account=$cmd_msg;
          fputs($fp,"WHOIS $nick\r\n");
        }*/
      }
      elseif (iscmd($msg,$cmd_msg,CMD_ACCOUNT)==True)
      {
        if ($cmd_msg<>"")
        {
          $account=$cmd_msg;
          fputs($fp,"WHOIS $account\r\n");
        }
        else
        {
          privmsg("Nick not specified");
        }
      }
      elseif (iscmd($msg,$cmd_msg,CMD_KARMA)==True)
      {
        $karma=$cmd_msg;
      }
      elseif (strtoupper($msg)==CMD_KARMA)
      {
        $karma="";
      }
      else
      {
        privmsg(ABOUT);
      }
    }
    else
    {
      if ($msg<>"")
      {
        $words=explode(" ",$msg);
        process($words,$noun_to,$noun_from);
        process($words,$verb_to,"","","ing");
        $new_msg=implode(" ",$words);
        if ($new_msg<>$msg)
        {
          privmsg($new_msg);
        }
      }
    }
  }
  if ((strpos($msg,TRIGGER)===False) and ($nick<>NICK))
  {
    $last=$msg;
  }
  else
  {
    $last="";
  }
  if (($joined==0) and (strpos($data,"End of /MOTD command")!==False))
  {
    $joined=1;
    fputs($fp,"JOIN ".CHAN."\r\n");
  }
  if (strpos($account,"/")!==False)
  {
    privmsg($account);
    $account="";
  }
  if (($karma<>"") and ($karma_delay>2))
  {
    privmsg($karma."++");
    $karma_delay=0;
  }
  $karma_delay++;
}

function privmsg($msg)
{
  global $fp;
  global $prefix;
  global $suffix;
  global $color;
  if ($color==-1)
  {
    $out=$msg;
  }
  else
  {
    $out=$prefix.$color.$msg.$suffix;
  }
  fputs($fp,":".NICK." PRIVMSG ".CHAN." :$out\r\n");
  echo "$msg\r\n";
}

function msg_nick($data,&$nick,&$msg)
{
  $parts=explode(" ",$data);
  if (count($parts)>1)
  {
    if ((trim($parts[1])=="PRIVMSG") and (count($parts)>3))
    {
      $pieces1=explode("!",$parts[0]);
      $pieces2=explode("PRIVMSG ".CHAN." :",$data);
      if ((count($pieces1)>1) and (count($pieces2)==2))
      {
        $nick=substr($pieces1[0],1);
        $msg=trim($pieces2[1]);
        return True;
      }
    }
  }
  $nick="";
  $msg="";
  return False;
}

function iscmd($msg,&$cmd_msg,$cmd)
{
  if (strtoupper(substr($msg,0,strlen($cmd)+1))==($cmd." "))
  {
    $cmd_msg=substr($msg,strlen($cmd)+1);
    return True;
  }
  $cmd_msg="";
  return False;
}

function process(&$words,&$to_lib,$from_lib="",$prefix="",$suffix="")
{
  for ($i=0;$i<count($words);$i++)
  {
    if (mt_rand(0,4)==1)
    {
      continue;
    }
    if ($suffix<>"")
    {
      if (substr(strtolower($words[$i]),strlen($words[$i])-strlen($suffix))==$suffix)
      {
        replace($words,$to_lib,$i);
      }
    }
    elseif ($prefix<>"")
    {
      if (substr(strtolower($words[$i]),0,strlen($prefix))==$prefix)
      {
        replace($words,$to_lib,$i);
      }
    }
    elseif (is_array($from_lib)==True)
    {
      if (in_array(strtolower($words[$i]),$from_lib)==True)
      {
        replace($words,$to_lib,$i);
      }
    }
    else
    {
      replace($words,$to_lib,$i);
    }
  }
  reset_lib($to_lib);
}

function replace(&$words,&$to_lib,$i)
{
  do
  {
    $j=mt_rand(0,count($to_lib)-1);
    check_all_used($to_lib);
  }
  while ($to_lib[$j][0]=="!");
  $words[$i]=$to_lib[$j];
  $to_lib[$j]="!".$to_lib[$j];
}

function reset_lib(&$lib)
{
  for ($i=0;$i<count($lib);$i++)
  {
    if ($lib[$i][0]=="!")
    {
      $lib[$i]=substr($lib[$i],1);
    }
  }
}

function check_all_used(&$lib)
{
  for ($i=0;$i<count($lib);$i++)
  {
    if ($lib[$i][0]<>"!")
    {
      return;
    }
  }
  reset_lib($lib);
}

function vote_init($data)
{

}

/*

http://soylentnews.org/~prospectacle/journal/241

<?php
 
# How to use this script:
 
# 1 - Ask people to write votes that look like this:
#     some candidate name = 1
#     some other = 2
#     another_one = 4
#     Any other text will just be ignored.
#     Any invalid = votes will be ignored, too
# 2 - Collect votes. e.g. via email, forum-comments, or a special web-form.
# 3 - Put all the votes in an array. Each vote should contain its vote-text, and a User ID.
# 4 - Run this script to filter, parse and count the votes.
#  
 
// Example candidates:
$valid_candidates = array(
    "candidateone",
    "another candidate",
    "yet another option",
    "somethingelse");
 
// Example votes:
$votes_array = array(
    // Upper case or lower case doesn't matter.
    array(
        "user_id"=>234,
        "text"=>"
            candidateOne = 1
            Another Candidate = 2
            SomethingElse = 3
        "),
    // Duplicate user. This will be handled properly.
    array(
        "user_id"=>234,
        "text"=>"
            Oops forgot one I like:
            Yet Another Option = 4
            Did I mention:
            CandidateOne = 1
        "
        ),
    // This one contains mostly invalid rankings, and one valid one.
    array(
        "user_id"=>345,
        "text"=>"
            // I hate CandidateOne
            CandidateOne = 6
            Another Candidate = 1
            Yet Another Option = 1
            My friend who's not listed = 3
        ")
    );
 
// Some options on how the votes are counted.
$allow_duplicate_ranks = false;
$allow_write_in_candidates = false;
 
// If you allow write-in candidates, specify a maximum possible rank.
// Otherwise the maximum rank equals the number of candidates.
if ($allow_write_in_candidates) $maximum_rank = 10;
else $maximum_rank = count($valid_candidates);
 
// put valid-user filter in here if necessary
function valid_user($user_id){return true;}
 
// Arrays to store the counted votes in:
$votes_by_voter = array();
$votes_by_candidate = array();
 
// Process all votes
foreach ($votes_array as $vote)
{
 
  // Is it a valid registered user?
  if (valid_user($vote["user_id"]))
  {
 
    // Process each line of the vote
    $vote_lines = explode("\n", trim($vote["text"]));
    foreach ($vote_lines as $this_line)
    {
 
      // Does it have an equals sign
      $equals_sign = strpos($this_line, "=");
      if ($equals_sign !== false)
      {
 
        // Does it have only one equals sign?
        $cleaned_up_line_text = trim($this_line, ";.!\t\n\r\0");
        $parts_of_line = explode("=", $cleaned_up_line_text);
        if (count($parts_of_line) == 2)
        {
 
          // Get the candidate and rank, make sure they're valid.
          $candidate = strtolower(trim($parts_of_line[0]));
          $candidate_is_valid = in_array($candidate, $valid_candidates);
          $rank = intval(trim($parts_of_line[1]));
          $rank_is_valid = ( ($rank > 0) && ($rank <= $maximum_rank) );
 
          // Proceed if (it's a valid rank number) and
          // (the candidate is valid, or we're allowing write-in candidates).
          if (($rank_is_valid) && ($candidate_is_valid || $allow_write_in_candidates))
          {
            // Get the score for this candidate.
            // The score is: maximum_rank - (this_rank - 1).
            // For example:
            // - Say there are 5 candidates and the maximum rank is 5
            // - A rank of 1 give it a score of 5.
            // - A rank of 2 gives it a score of 4.
            // - A rank of 5 gives a score of 1.
            // See "Borda Count".
            $score = $maximum_rank - ($rank-1);
 
            // If this is the voter's first vote, create a voting-record for them.
            // This keeps track of which candidates and ranks they've already voted.
            $voter = $vote["user_id"];
            if (!isset($votes_by_voter[$voter]))
            {
              $votes_by_voter[$voter]["candidates"] = array();
              $votes_by_voter[$voter]["ranks"] = array();
            }
 
            // Make sure this user hasn't already voted on this candidate
            if (!isset($votes_by_voter[$voter]["candidates"][$can didate]))
            {
 
              // Make sure the user hasn't already assigned this rank number,
              // or that we're allowing duplicate ranks.
              if ($allow_duplicate_ranks || (!isset($votes_by_voter[$voter]["ranks"][$rank])))
              {
 
                // Remember that this voter has voted for this candidate,
                // and has used up this rank.
                $votes_by_voter[$voter]["candidates"][$candidate] = true;
                $votes_by_voter[$voter]["ranks"][$rank] = true;
 
                // Count the vote towards the total for this candidate.
                if (!isset($votes_by_candidate[$candidate]))
                  $votes_by_candidate[$candidate] = $score;
                else $votes_by_candidate[$candidate] += $score;
 
              } // End of checking if this rank is a duplicate for this voter.
            } // End of check checking if candidate is a duplicate for this voter.
          } // End of check for valid vote values.
        } // End of check for correctly formatted vote
      } // End of check for equals sign
    } // End of for loop for lines of vote text.
  } // of check for valid user.
} // end of for loop for all votes.
 
print "Who have voters voted for, and which ranks have they used?:<pre>";
print_r($votes_by_voter);
print "</pre><Br>";
print "What score does each candidate end up with<pre>";
// Sort the candidates from highest to lowest
arsort($votes_by_candidate);
print_r($vot es_by_candidate);
print "</pre>";
 
?>
*/

?>
