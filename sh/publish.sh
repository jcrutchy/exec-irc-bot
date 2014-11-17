#!/bin/bash

# copy to bottom of /.git/config
# [alias]
#  up = !bash sh/publish.sh

bash /nas/server/git/exec-irc-bot/sh/push.sh
bash /nas/server/git/exec-irc-bot/sh/sync.sh
exit 0
