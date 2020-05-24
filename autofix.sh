#!/usr/bin/env bash

# Script to run php-cs-fixer on all code.
# Good idea to run this prior to pushing any code up.

ENVIRON=$1


if [[ "$ENVIRON" == "CI" ]]; then
  # Run this in CI (continuous integration) mode, where it will error out if any file needs to be fixed
  echo "Running php-cs-fixer in --dry-run mode"
  vendor/bin/php-cs-fixer fix --config=.php_cs.dist -v --using-cache=no --dry-run
  RESULT=$?
  if [[ "$RESULT" != 0 ]]; then
    echo "------------------------------------------------------------"
    echo "ERROR: Detected code standard issue."
    echo "Please run ./autofix.sh to fix this, then commit the result."
    echo "------------------------------------------------------------"
    exit 1
  fi
  echo "------------------------------------------------------------"
  echo "SUCCESS. All files matched standards."
  echo "------------------------------------------------------------"
else
  # Run in normal mode, where the files will be automatically fixed (and will need to be committed).
  echo "Running php-cs-fixer in real mode."
  exec vendor/bin/php-cs-fixer fix --config=.php_cs.dist --using-cache=no
fi
