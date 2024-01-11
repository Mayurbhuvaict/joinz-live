#!/bin/bash

# Define the path to your PHP binary and the full path to your PHP script
#PHP_BINARY="/usr/bin/php"
SCRIPT_PATH="bin/console joinz:import"

# Infinite loop to continuously check the time
while true; do
	    # Get the current time in the format HH:MM
	        current_time=$(date +%H:%M)
		    
		    # Check if the current time is midnight (00:00)
		        if [ "$current_time" == "00:00" ]; then
echo "Starting to run"		        
# Execute the PHP script
					        $SCRIPT_PATH
						        
						        # Sleep for 24 hours to wait until the next midnight
							        sleep 86400  # 86400 seconds = 24 hours
								    else
									            # Sleep for 1 minute and check again
		#echo "It's not midnight"								            sleep 60  # 60 seconds = 1 minute
											        fi
											done

