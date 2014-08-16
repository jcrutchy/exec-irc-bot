#!/bin/bash

# copy to bottom of /.git/config
# [alias]
#  up = !bash sh/publish.sh $1

bash /nas/server/git/exec-irc-bot/sh/sync.sh
if [ "$1" = "" ]; then
  bash /nas/server/git/exec-irc-bot/sh/push.sh "update"
else
  bash /nas/server/git/exec-irc-bot/sh/push.sh "$1"
fi

exit 0
