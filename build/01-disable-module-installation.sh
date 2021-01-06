#!/bin/bash

# This script is sourced as a $MAGENTO_PRE_INSTALL_SCRIPT from in a github action by
# https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh

echo "Disabling module ${COMPOSER_NAME} during initial installation"
mv -v ${MAGENTO_ROOT}/local-source/${GITHUB_ACTION}/registration.php \
      ${MAGENTO_ROOT}/local-source/${GITHUB_ACTION}/registration-disabled.php

echo "<?php" > ${MAGENTO_ROOT}/local-source/${GITHUB_ACTION}/registration.php
