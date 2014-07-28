<?php

# gpl2
# by crutchy
# 27-july-2014

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

#####################################################################################################

?>
