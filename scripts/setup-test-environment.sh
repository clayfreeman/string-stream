#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
export PHP_VERSION='8.1'

echo Install required packages ... >&2
sudo apt-get remove --purge php*
sudo apt-add-repository -y ppa:ondrej/php
sudo apt-get install --no-install-recommends --no-install-suggests -y \
  curl p7zip-full php$PHP_VERSION-{cli,curl,mbstring,xml,zip} unzip

echo Set the default version of PHP ... >&2
sudo update-alternatives --set php $(which php$PHP_VERSION)

echo Install the PCOV extension for PHP ... >&2
sudo pecl install pcov
echo extension=pcov.so | sudo tee /etc/php/$PHP_VERSION/mods-available/pcov.ini

echo Enable the PCOV extension for PHP ... >&2
sudo phpenmod pcov

echo Show the current PHP version and its enabled extensions ... >&2
php -v
php -m

echo Download and verify the Composer installer ... >&2
curl 'https://getcomposer.org/installer' > composer-setup.php

ACTUAL_HASH=$(sha384sum composer-setup.php | awk '{print $1}')
EXPECT_HASH=dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6

if [[ "$ACTUAL_HASH" != "$EXPECT_HASH" ]]
then
  echo ERROR: Composer installer checksum verification failed. >&2
  exit 1
fi

echo Run and remove the Composer installer ... >&2
php composer-setup.php
rm composer-setup.php

echo Move the Composer executable to PATH ... >&2
sudo mv composer.phar /usr/local/bin/composer

echo Install all package dependencies ... >&2
composer install --no-interaction --no-progress
