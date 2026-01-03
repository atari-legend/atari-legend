#!/bin/bash

# Wrapper for decompressing PACK-ICE packed files
# Needs unice68 (which comes with sc68)
# or icecat from https://github.com/larsbrinkhoff/pack-ice

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

#$SCRIPT_DIR/unice68 "$1" > $2
$SCRIPT_DIR/icecat "$1" > $2
