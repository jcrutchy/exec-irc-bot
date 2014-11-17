#!/bin/bash
rsync -av /nas/server/git/exec-irc-bot/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/inc/
rsync -av /nas/server/git/data/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/data/
rsync -av /nas/server/git/pwd/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/pwd/
exit 0
