#!/bin/bash

#
# This script is intended to be sourced by a MAGENTO_POST_INSTALL_SCRIPT script in the
# https://github.com/extdn/github-actions-m2 integration test github action.
#
# Note: If this script exits with an error status, the calling script will fail, too.
#
# Reference: https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh#L90-L94
#

echo "Changing the current working directory to ${MAGENTO_ROOT}"
cd $MAGENTO_ROOT

echo "Running unit tests with PHP " $(php --version | head -1)

vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/hyva-themes/module-magento2-admin/Test/Unit

echo "Changing the current working directory back to it's previous location."
cd -
