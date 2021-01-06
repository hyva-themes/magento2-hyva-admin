#!/bin/bash

# This script is sourced as a $MAGENTO_POST_INSTALL_SCRIPT from in a github action by
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh

echo "Backporting Hyva_Admin to PHP 7.3 with rector"

cd $MAGENTO_ROOT
bin/magento setup:di:compile
cd -

composer require --dev bamarni/composer-bin-plugin
composer bin rectorphp require --dev rector/rector:0.8.8
${MAGENTO_ROOT}/vendor/bin/rector process \
    --config ${GITHUB_WORKSPACE}/build/rector.php \
    --autoload-file=${MAGENTO_ROOT}/vendor/autoload.php \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}

echo "Running unit tests"
${MAGENTO_ROOT}/vendor/bin/phpunit \
    --config ${MAGENTO_ROOT}/dev/tests/unit/phpunit.xml.dist \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/Test/Unit
