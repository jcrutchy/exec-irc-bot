#!/bin/bash
rsync -av /home/jared/git/exec-irc-bot/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/inc/
rsync -av /home/jared/git/data/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/data/
rsync -av /home/jared/git/pwd/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/pwd/
#rsync -av /home/jared/git/relay/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/relay/
rsync -av /var/www/irciv.us.to/ jared@192.168.0.21:/var/www/irciv.us.to/
exit 0
