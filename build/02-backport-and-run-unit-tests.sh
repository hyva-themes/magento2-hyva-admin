#!/bin/bash

# This script is sourced as a $MAGENTO_POST_INSTALL_SCRIPT from in a github action by
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh

cd ${MAGENTO_ROOT}

echo "Enabling module ${COMPOSER_NAME}"
mv -v ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/registration-disabled.php \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/registration.php
bin/magento module:enable ${MODULE_NAME}

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

echo "Running unit tests"
${MAGENTO_ROOT}/vendor/bin/phpunit \
    --config ${MAGENTO_ROOT}/dev/tests/unit/phpunit.xml.dist \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/Test/Unit

cd -
