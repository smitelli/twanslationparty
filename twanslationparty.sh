#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
/usr/bin/php $DIR/twanslationparty.php >> $DIR/debug.log 2>&1