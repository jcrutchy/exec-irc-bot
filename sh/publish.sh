#!/bin/bash

bash /nas/server/git/test/sh/sync.sh
if [ "$1" = "" ]; then
  bash /nas/server/git/test/sh/push.sh "update"
else
  bash /nas/server/git/test/sh/push.sh "$1"
fi
bash /nas/server/git/test/sh/test.sh

exit 0
