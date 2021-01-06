#!/bin/bash

# This script is sourced as a $PRE_PROJECT_SCRIPT from in a github action by
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh


echo "Setting min PHP version requirement to 7.3.0"
sed -i 's/"php":.*/"php": "^7.3.0",/' ${GITHUB_WORKSPACE}/composer.json
