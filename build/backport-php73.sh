#!/bin/bash

#
# This script is intended to be sourced by a MAGENTO_POST_INSTALL_SCRIPT script in the
# https://github.com/extdn/github-actions-m2 integration test github action.
#
# Note: If this script exits with an error status, the calling script will fail, too.
#
# Reference: https://github.com/extdn/github-actions-m2/blob/master/magento-integration-tests/entrypoint.sh#L90-L94
#


echo "Changing the current working directory to ${MAGENTO_ROOT}"
cd $MAGENTO_ROOT

echo "Backporting to PHP 7.3"
echo "Current working directory: " $(pwd)

bin/magento setup:di:compile
docker run --rm -v $(pwd):/project rector/rector:0.8.8 process --config /project/vendor/hyva-themes/module-magento2-admin/build/rector.php --autoload-file=/project/vendor/autoload.php /project/vendor/hyva-themes/module-magento2-admin

echo "Changing the current working directory back to it's previous location."
cd -
