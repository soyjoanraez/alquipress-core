#!/bin/bash
# Alquipress Log Watcher
# Usage: ./watch-logs.sh [custom|wp]

LOG_TYPE=${1:-custom}
CUSTOM_LOG="wp-content/alquipress-debug.log"
WP_LOG="wp-content/debug.log"

if [ "$LOG_TYPE" == "wp" ]; then
    LOG_FILE=$WP_LOG
else
    LOG_FILE=$CUSTOM_LOG
fi

echo "👀 Watching $LOG_FILE..."
tail -f $LOG_FILE
