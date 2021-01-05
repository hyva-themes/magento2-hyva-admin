#!/bin/bash

#
# This script is intended to be sourced as a MAGENTO_POST_INSTALL_SCRIPT script in the
# https://github.com/extdn/github-actions-m2 integration test github action.
#
# Note: if this script exits with an error status, the calling script will fail, too.
#
# Reference: https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh#L90-L94
#


BUILD_SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

echo "Sourcing backport to PHP 7.3 and unit test build scripts in directory" ${BUILD_SCRIPT_DIR}

. ${BUILD_SCRIPT_DIR}/backport-php73.sh
. ${BUILD_SCRIPT_DIR}/run-unit-tests.sh
