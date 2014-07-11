#!/bin/bash
rsync -avzru /nas/server/git/test/ jared@192.168.0.21:/var/include/vhosts/irciv.us.to/inc/
rsync -avzru jared@192.168.0.21:/var/include/vhosts/irciv.us.to/inc/ /nas/server/git/test/
exit 0
