#!/bin/bash

NIGHT_START="23:30"
NIGHT_END="06:00"

while /bin/true; do
    CURRENT_TIME=$(date +"%H:%M")
    if [[ "$CURRENT_TIME" > "$NIGHT_START" || "$CURRENT_TIME" < "$NIGHT_END" ]]; then
        PID=$(pidof php)
        if [[ $PID ]] ; then
            echo "Script is running: pid $PID"
        else
            echo "Script is not running."
            echo "Starting script..."
            bin/console joinz:import > import-$(date +"%Y-%m-%d-%H:%M:%S").out &
            #bin/console joinz:import &
        fi
    else
        echo "It's not night time. Exiting..."
        exit 0
    fi

    sleep 30
done



