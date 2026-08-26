#!/bin/bash
rsync -av /home/jared/git/exec-irc-bot/ jared@192.168.0.21:/var/include/vhosts/irciv.bot.nu/inc/
rsync -av /home/jared/git/data/ jared@192.168.0.21:/var/include/vhosts/irciv.bot.nu/data/
rsync -av /home/jared/git/pwd/ jared@192.168.0.21:/var/include/vhosts/irciv.bot.nu/pwd/
#rsync -av /home/jared/git/relay/ jared@192.168.0.21:/var/include/vhosts/irciv.bot.nu/relay/
rsync -av /var/www/irciv.bot.nu/ jared@192.168.0.21:/var/www/irciv.bot.nu/
exit 0
