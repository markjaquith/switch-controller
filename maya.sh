#!/bin/bash
# get the current directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cat $DIR/groups.json | jq '.[] | select(.name == "Maya") | .qos_rate_max_down'
