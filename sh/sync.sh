#!/bin/bash
rsync -av ./ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/inc/
rsync -av ../data/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/data/
rsync -av ../pwd/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/pwd/
rsync -av ../relay/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/relay/
rsync -av /var/www/irciv.us.to/ jared@192.168.0.21:/var/www/irciv.us.to/
exit 0
