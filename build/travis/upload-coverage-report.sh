#! /bin/bash
set -ex

BASE_PATH=$(pwd)
MW_INSTALL_PATH=$BASE_PATH/../mw

if [ "$TYPE" == "coverage" ]
then
	# Use for coveralls.io but currently no working
	# composer require satooshi/php-coveralls:dev-master
	# php vendor/bin/coveralls -v

	#wget https://scrutinizer-ci.com/ocular.phar
	#php ocular.phar code-coverage:upload --format=php-clover $BASE_PATH/build/coverage.clover
	cd $MW_INSTALL_PATH

	composer require 'scrutinizer/ocular=1.1.*' --prefer-source
	ocular code-coverage:upload --format=php-clover $BASE_PATH/build/coverage.clover

fi