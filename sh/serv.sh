#!/bin/bash
rsync -av /nas/server/git/exec-irc-bot/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/inc/
exit 0
