#!/bin/sh

# This file is run locally which create hash file needed for deployed version
# Expects branch parameter

read -r -p 'Type in branch name: ' branch

if [ "$branch" == "" ]; then
     echo "Parameter 1 is required: (branch)"
     exit
fi

CURRENT_HASH="$(git rev-parse $branch)"
echo "${CURRENT_HASH}" > .git-hash

git add . 
git commit -m "$branch new hash file: $CURRENT_HASH"
git push origin ${branch}