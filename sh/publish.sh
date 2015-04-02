#!/bin/bash

# copy to bottom of /.git/config
# [alias]
#  up = !bash sh/publish.sh

bash ./sh/push.sh
bash ./sh/sync.sh
exit 0
