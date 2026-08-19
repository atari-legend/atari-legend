#!/bin/bash
# -
# Script to deploy the code to the dev server via rsync
set -eu

RSYNC_FLAGS=(
    -avvz
    # Delete anything on the server that is not in the repository, except what
    # the rules below protect
    --delete

    # Filter rule prefixes, first match wins:
    #
    #   -   exclude: not sent, and --delete leaves the server's copy alone
    #   P   protect: --delete leaves the server's copy alone, ours is still sent
    #
    # A pattern without a leading / matches at any depth.

    # -- Built in the CI workspace, no business on the server -----------------
    "--filter=- node_modules"
    "--filter=- .git"
    # Coverage report, build/logs/clover.xml. Anchored, because public/build is
    # the Vite output and has to be sent.
    "--filter=- /build"
    # E2E artefacts: the reports can be large and the auth state holds a live
    # admin session cookie. They are gitignored, but the CI job produces them
    # in the workspace before this script runs.
    "--filter=- playwright-report"
    "--filter=- test-results"
    "--filter=- blob-report"
    "--filter=- tests/e2e/.auth"

    # -- The server's own state -----------------------------------------------
    "--filter=- .env"
    # Uploads, sessions, caches, logs. The repository tracks nothing in here
    # but the .gitignore skeleton, which the mkdir below recreates. Anchored,
    # so it cannot catch a vendor/ directory of the same name.
    "--filter=- /storage"
    # A symlink into the above, absolute and therefore host-specific: CI makes
    # its own for the e2e run, the server creates and keeps its own.
    "--filter=- public/storage"

    # -- Ours to send, the server's to write ----------------------------------
    # The daily database and images exports are generated on the server, into a
    # folder that is otherwise part of the repository: --delete must not take
    # the exports, while HEADER.html and .htaccess still have to be sent.
    "--filter=P public/data/database-dumps/**"

    # Safety net in case DEPLOY_PATH points at the wrong folder - the
    # deployment user can reach the home directory holding these other sites.
    # Unanchored: a wrong path could put them at any depth, and nothing in this
    # repository goes by these names.
    "--filter=P _marcer"
    "--filter=P al-database-backups"
    # Same case, for a logs folder at the deploy root.
    "--filter=P /logs"
)

DEPLOY_USER=$1
DEPLOY_HOST=$2
DEPLOY_PATH=$3

if [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_HOST" ] || [ -z "$DEPLOY_PATH" ] ; then
    echo "Missing mandatory deployment arguments"
    exit
fi

mkdir -p ~/.ssh/
ssh-keyscan $DEPLOY_HOST >> ~/.ssh/known_hosts

rsync "${RSYNC_FLAGS[@]}" . $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH/

# storage/ is excluded from the rsync, so a deployment target that has none yet
# needs one built here. app/public holds the site data - screenshots, scans,
# dump ZIPs - and framework/ is Laravel's scaffolding, without which artisan
# optimize fails on view:cache before the site serves a request.
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && mkdir -p \
    storage/app/public \
    storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs"

ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && { test -e public/storage || php8.4-cli artisan storage:link --force; }"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan migrate --force"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan config:clear"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan optimize:clear"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan optimize"
ssh $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_PATH && php8.4-cli artisan sndh:fetch"
