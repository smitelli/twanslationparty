#!/usr/bin/env bash
set -e

SELF_DIR="$( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )"

php $SELF_DIR/twanslationparty.php
