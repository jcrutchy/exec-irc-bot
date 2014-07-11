#!/bin/bash

bash /nas/server/git/test/sh/sync.sh
if [ $1 -eq "" ]; then
 bash /nas/server/git/test/sh/push.sh "update"
else
 bash /nas/server/git/test/sh/push.sh "$1"
fi

exit 0
