#!/bin/bash
bin/console joinz:import > import-$(date +"%Y-%m-%d-%H:%M:%S").out &
PID=$(pidof php)

while /bin/true; do
	echo $PID;
	memory_usage=`free -m | awk 'NR==2{print $3*100/$2 }'`
	if [ `echo "${memory_usage} > 80.0" | bc` -eq 1 ] ; then
		echo "Killing PID: $PID"
		kill $PID;
		echo "Starting command import-$(date +"%Y-%m-%d-%H:%M:%S").out";
		bin/console joinz:import > import-$(date +"%Y-%m-%d-%H:%M:%S").out &
		PID=$(pidof php)
	else
		echo "Continue.. $memory_usage"
	fi
	sleep 30
done
