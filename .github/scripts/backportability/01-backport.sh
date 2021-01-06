#!/bin/bash

# modified version of
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh
#
# the difference:
# assume Magento installation exists, install and execute rector

set -e

echo "Running custom entrypoint ${0}"

test -z "${COMPOSER_NAME}" && COMPOSER_NAME=$INPUT_COMPOSER_NAME

test -z "${COMPOSER_NAME}" && (echo "'composer_name' is not set in your GitHub Actions YAML file" && exit 1)

php --version | head -1 | grep -q 7.4 || (echo "The ${0} requires PHP 7.4" && exit 1)

MAGENTO_ROOT=/tmp/m2
PROJECT_PATH=$GITHUB_WORKSPACE

cd ${MAGENTO_ROOT}

echo "Backporting Hyva_Admin to PHP 7.3 with rector"
echo "step 1: compile generated/code"
bin/magento setup:di:compile

echo "step 2: install rector"
composer require --dev bamarni/composer-bin-plugin
composer bin rectorphp require --dev rector/rector:0.8.8

echo "step 3: run rector"
${MAGENTO_ROOT}/vendor/bin/rector process \
    --config ${GITHUB_WORKSPACE}/build/rector.php \
    --autoload-file=${MAGENTO_ROOT}/vendor/autoload.php \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}

cd -
