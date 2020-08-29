#!/bin/bash
# -
# Script to deploy the code to the dev server via rsync
set -eu

RSYNC_FLAGS=(
    -alvvzn
    # Delete all unknown files, except the ones excluded below
    --delete
    # Do not sync environment file
    --exclude .env
    # Do not sync NPM modules
    --exclude node_modules
    # Do not sync the Git folder
    --exclude .git
    # Exclude public storage folders
    --exclude public/public
    --exclude storage/app/public
)

DEPLOY_USER=$1
DEPLOY_HOST=$2
DEPLOY_PATH=$3

if [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_HOST" ] || [ -z "$DEPLOY_PATH" ]; then
    echo "Missing mandatory deployment arguments"
    exit
fi

mkdir -p ~/.ssh/
ssh-keyscan $DEPLOY_HOST >> ~/.ssh/known_hosts

rsync ${RSYNC_FLAGS[@]} . $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH/
