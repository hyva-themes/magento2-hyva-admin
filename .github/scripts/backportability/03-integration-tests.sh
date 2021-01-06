#!/bin/bash

# modified version of
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh
#
# the difference:
# assume Magento installation exists, execute integration tests

set -e

echo "Running custom entrypoint ${0}"

test -z "${COMPOSER_NAME}" && COMPOSER_NAME=$INPUT_COMPOSER_NAME
test -z "${PHPUNIT_FILE}" && PHPUNIT_FILE=$INPUT_PHPUNIT_FILE

test -z "${MAGENTO_ROOT}" && (echo "'MAGENTO_ROOT' is not set in the environment" && exit 1)
test -z "${COMPOSER_NAME}" && (echo "'composer_name' is not set in your GitHub Actions YAML file" && exit 1)

php --version | head -1 | grep -q 7.3 || (echo "The ${0} requires PHP 7.3" && exit 1)

echo "Using MAGENTO_ROOT: ${MAGENTO_ROOT}"
PROJECT_PATH=$GITHUB_WORKSPACE

cd ${MAGENTO_ROOT}

echo "Trying phpunit.xml file $PHPUNIT_FILE"
if [[ ! -z "$PHPUNIT_FILE" ]] ; then
    PHPUNIT_FILE=${GITHUB_WORKSPACE}/${PHPUNIT_FILE}
fi

if [[ ! -f "$PHPUNIT_FILE" ]] ; then
    PHPUNIT_FILE=/docker-files/phpunit.xml
fi
echo "Using PHPUnit file: $PHPUNIT_FILE"

echo "Prepare for integration tests"

sed "s#%COMPOSER_NAME%#$COMPOSER_NAME#g" $PHPUNIT_FILE > dev/tests/integration/phpunit.xml

php -r "echo ini_get('memory_limit').PHP_EOL;"

echo "Run the integration tests"
cd $MAGENTO_ROOT/dev/tests/integration && ../../../vendor/bin/phpunit -c phpunit.xml
