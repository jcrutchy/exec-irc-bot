#!/bin/bash

git add *
if [ "$1" = "" ]; then
  git commit -a -m "update"
else
  git commit -a -m "$1"
fi
git push
exit 0
