#!/usr/bin/env bash

# Cause the script to exit out on any command error, instead of continuing onward
set -e

# This script (ci = "continuous integration") handles running various checks:
# - php-cs-fixer - code style autofixer
# - phpstan - static analyzer
# - phpunit
# If the first argument passed in is TRAVIS, it will run in the context of travis-ci,
# meaning we only run php-cs-fixer in this script.

ENVIRON=$1


if [[ "$ENVIRON" == "TRAVIS" ]]; then
  # Run this in CI (continuous integration) mode, where it will error out if any file needs to be fixed
  echo "Running php-cs-fixer in --dry-run mode"
  vendor/bin/php-cs-fixer fix --config=.php_cs.dist -v --using-cache=no --dry-run
  RESULT=$?
  if [[ "$RESULT" != 0 ]]; then
    echo "------------------------------------------------------------"
    echo "ERROR: Detected code standard issue."
    echo "Please run ./run_ci.sh to fix this, then commit the result to your branch."
    echo "------------------------------------------------------------"
    exit 1
  fi
  echo "------------------------------------------------------------"
  echo "SUCCESS. All files matched standards."
  echo "------------------------------------------------------------"
else
  # Run in normal mode, where the files will be automatically fixed (and will need to be committed).
  echo "Running php-cs-fixer in real mode..."
  vendor/bin/php-cs-fixer fix --config=.php_cs.dist
  echo "Running phpstan..."
  vendor/bin/phpstan analyse --level 1 src tests
  echo "Running unit tests..."
  vendor/bin/phpunit
fi
