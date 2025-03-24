#!/usr/bin/env bash
set -xeuo pipefail

# Install required packages.
sudo apt-add-repository -y ppa:ondrej/php
sudo apt-get -qq install curl php8.1-cli php8.1-curl php8.1-mbstring php8.1-xdebug php8.1-xml php8.1-zip unzip

# Enable the XDebug extension for PHP.
sudo phpenmod xdebug

# Show the current PHP version and its enabled extensions.
php -v; echo
php -m

# Download and install Composer.
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php

# Remove the Composer installer.
php -r "unlink('composer-setup.php');"

# Move the Composer executable to PATH.
sudo mv composer.phar /usr/local/bin/composer

# Install all package dependencies.
composer install --no-interaction --no-progress
