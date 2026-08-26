<?php

#####################################################################################################

/*
exec:add ~story-search
exec:edit ~story-search timeout 120
exec:edit ~story-search accounts_wildcard *
exec:edit ~story-search cmd php scripts/story_search.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%% %%server%%
exec:enable ~story-search
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];
$server=$argv[6];

# TODO: SEARCH SN ARTICLES & SUBMISSIONS FOR KEYWORDS TO HELP WHEN CONSIDERING A SUBMISSION TO WORK OUT IF A SIMILAR STORY HAS ALREADY BEEN SUBMITTED

#####################################################################################################

?>
