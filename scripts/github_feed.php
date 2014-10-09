<?php

# gpl2
# by crutchy

#####################################################################################################

ini_set("display_errors","on");

date_default_timezone_set("UTC");

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

$host="api.github.com";
$port=443;

$uri="/repos/crutchy-/exec-irc-bot/events";

$response=wget($host,$uri,$port,ICEWEASEL_UA,"",60);
$content=strip_headers($response);


/*
  "url": "https://api.github.com/repos/crutchy-/exec-irc-bot",
  "forks_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/forks",
  "keys_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/keys{/key_id}",
  "collaborators_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/collaborators{/collaborator}",
  "teams_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/teams",
  "hooks_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/hooks",
  "issue_events_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/issues/events{/number}",
  "events_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/events",
  "assignees_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/assignees{/user}",
  "branches_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/branches{/branch}",
  "tags_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/tags",
  "blobs_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/git/blobs{/sha}",
  "git_tags_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/git/tags{/sha}",
  "git_refs_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/git/refs{/sha}",
  "trees_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/git/trees{/sha}",
  "statuses_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/statuses/{sha}",
  "languages_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/languages",
  "stargazers_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/stargazers",
  "contributors_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/contributors",
  "subscribers_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/subscribers",
  "subscription_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/subscription",
  "commits_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/commits{/sha}",
  "git_commits_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/git/commits{/sha}",
  "comments_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/comments{/number}",
  "issue_comment_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/issues/comments/{number}",
  "contents_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/contents/{+path}",
  "compare_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/compare/{base}...{head}",
  "merges_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/merges",
  "archive_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/{archive_format}{/ref}",
  "downloads_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/downloads",
  "issues_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/issues{/number}",
  "pulls_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/pulls{/number}",
  "milestones_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/milestones{/number}",
  "notifications_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/notifications{?since,all,participating}",
  "labels_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/labels{/name}",
  "releases_url": "https://api.github.com/repos/crutchy-/exec-irc-bot/releases{/id}",
*/

$data=json_decode($content,True);

$n=count($data);
for ($i=0;$i<$n;$i++)
{
  # 2014-08-24T11:30:30Z
  $timestamp=$data[$i]["created_at"];
  $t=convert_timestamp($timestamp,"Y-m-d H:i:s ");
  $dt=microtime(True)-$t;
  if ($dt<=300) # 5 minutes
  {
    pm("#github",$data[$i]["type"]." ".$data[$i]["login"]." - ".$data[$i]["repo"]["name"]);
    return;
  }
}

#####################################################################################################

?>
