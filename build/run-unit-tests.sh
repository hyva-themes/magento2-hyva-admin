#!/bin/bash

#
# This script is intended to be used in a MAGENTO_POST_INSTALL_SCRIPT script in the
# https://github.com/extdn/github-actions-m2 integration test github action.
#
# If this script exits with an error status, the calling script will fail, too, because the -e bash option is set.
#
# Reference: https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh#L90-L94
#

set -e

echo "Running unit tests with PHP " $(php --version | head -1)

vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/hyva-themes/module-magento2-admin/Test/Unit
