#!/bin/bash

# This script is sourced as a $MAGENTO_PRE_INSTALL_SCRIPT from in a github action by
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh

echo "Disabling module ${COMPOSER_NAME} during initial installation"
mv -v ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/registration.php \
      ${MAGENTO_ROOT}/vendor/${COMPOSER_NAME}/registration-disabled.php
