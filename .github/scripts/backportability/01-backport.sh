#!/bin/bash

# modified version of
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh
#
# the difference:
# assume Magento installation exists, install and execute rector

set -e

echo "Running custom entrypoint ${0}"

echo MAGENTO_ROOT=$MAGENTO_ROOT
echo MODULE_SOURCE=$MODULE_SOURCE

test -z "${COMPOSER_NAME}" && COMPOSER_NAME=$INPUT_COMPOSER_NAME

test -z "${MAGENTO_ROOT}" && (echo "'MAGENTO_ROOT' is not set in the environment" && exit 1)
test -z "${MODULE_SOURCE}" && (echo "'MODULE_SOURCE' is not set in the environment" && exit 1)
test -z "${COMPOSER_NAME}" && (echo "'composer_name' is not set in your GitHub Actions YAML file" && exit 1)

php --version | head -1 | grep -q 7.4 || (echo "The ${0} requires PHP 7.4" && exit 1)

cd ${MAGENTO_ROOT}

echo "Backporting Hyva_Admin to PHP 7.3 with rector"
echo "::group::Run bin/magento setup:di:compile"
bin/magento setup:di:compile
echo "::endgroup::"

echo "::group::Install rector"
composer require --dev bamarni/composer-bin-plugin
composer bin rectorphp require --dev "nikic/php-parser:4.10.4 as 4.10.2"
composer bin rectorphp require --dev rector/rector:0.8.56

echo "Force composer-bin install of phpstan to be used"
cd vendor/bin
rm phpstan phpstan.phar
ln -s ../../vendor-bin/rectorphp/phpstan/phpstan ./phpstan
ln -s ../../vendor-bin/rectorphp/phpstan/phpstan.phar ./phpstan.phar
cd -
echo "::endgroup::"

echo "group::Run rector process"
${MAGENTO_ROOT}/vendor/bin/rector process \
    --config ${MODULE_SOURCE}/build/rector.php \
    --autoload-file=${MAGENTO_ROOT}/vendor/autoload.php \
    ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}
echo "::endgroup::"

cd -
