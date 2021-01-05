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

BUILD_SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
${BUILD_SCRIPT_DIR}/backport-php73.sh
${BUILD_SCRIPT_DIR}/run-unit-tests.sh
