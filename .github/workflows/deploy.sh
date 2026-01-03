#!/bin/bash
# -
# Script to deploy the code to the dev server via rsync
set -eu

RSYNC_FLAGS=(
    -alvvz
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
    # Exclude folder storing user sessions
    --exclude storage/framework/sessions

    # Some safety patterns below in case the development points to the
    # wrong older, as the deployment user has access to the root folder
    # containing other things...

    # _elite folder containing another site
    --exclude _elite
    # _stonish folder containing another site
    --exclude _stonish
    # Do not delete logs
    --exclude logs
)

DEPLOY_USER=$1
DEPLOY_HOST=$2
DEPLOY_PATH=$3
LEGACY_PATH=${4:-""}

if [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_HOST" ] || [ -z "$DEPLOY_PATH" ] ; then
    echo "Missing mandatory deployment arguments"
    exit
fi

mkdir -p ~/.ssh/
ssh-keyscan $DEPLOY_HOST >> ~/.ssh/known_hosts

rsync ${RSYNC_FLAGS[@]} . $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH/

# Create link to production data folder, if it does not already exist
if [ ! -z "$LEGACY_PATH" ]; then
    ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH/storage/app/ && test -h public || ln -s ../../../$LEGACY_PATH/data public"
fi

ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan storage:link"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan migrate --force"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan config:clear"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan optimize:clear"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan optimize"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan sndh:fetch"
