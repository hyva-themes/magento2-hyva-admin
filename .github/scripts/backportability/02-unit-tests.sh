#!/bin/bash

# modified version of
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh
#
# the difference:
# assume Magento installation exists, execute unit tests

set -e

echo "Running custom entrypoint ${0}"

test -z "${COMPOSER_NAME}" && COMPOSER_NAME=$INPUT_COMPOSER_NAME

test -z "${MAGENTO_ROOT}" && (echo "'MAGENTO_ROOT' is not set in the environment" && exit 1)
test -z "${COMPOSER_NAME}" && (echo "'composer_name' is not set in your GitHub Actions YAML file" && exit 1)
test -z "${MAGENTO_VERSION}" && (echo "'ce_version' is not set in your GitHub Actions YAML file" && exit 1)

php --version | head -1 | grep -q 7.3 || (echo "The ${0} requires PHP 7.3" && exit 1)

echo "Using MAGENTO_ROOT: ${MAGENTO_ROOT}"
PROJECT_PATH=$GITHUB_WORKSPACE

echo "Running unit tests"
${MAGENTO_ROOT}/vendor/bin/phpunit \
    --config ${MAGENTO_ROOT}/dev/tests/unit/phpunit.xml.dist \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/Test/Unit
