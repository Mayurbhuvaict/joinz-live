#!/bin/bash
#bin/console joinz:import > import-$(date +"%Y-%m-%d-%H:%M:%S").out &
while /bin/true; do
	PID=$(pidof php)
	if [[ $PID ]] ; then
		echo "Script is running: pid $PID"
	else
		echo "Script is not running."
		echo "Starting script..."
#		bin/console joinz:import > import-$(date +"%Y-%m-%d-%H:%M:%S").out &
		bin/console joinz:import &
	fi
	sleep 30
done
