#!/bin/bash

while /bin/true; do
	bin/console joinz:import > import-$(date +"%Y-%m-%d-%H:%M:%S").out &
	sleep 30
done
