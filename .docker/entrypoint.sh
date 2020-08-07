#!/bin/sh

until nc -z -v -w30 mysql 3306
do
  echo "Waiting for database connection..."
  sleep 5
done

make -C /opencart-module run

mkdir /opencart-module/www/system/storage/session

echo '\nini_set("session.save_path", DIR_SYSTEM . "storage/session");' >> /opencart-module/www/config.php
echo '\nini_set("session.save_path", DIR_SYSTEM . "storage/session");' >> /opencart-module/www/admin/config.php

#chown -R www-data:www-data /opencart-module \
#    && find /opencart-module -type d -exec chmod 755 {} \; \
#    && find /opencart-module -type f -exec chmod 644 {} \;

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- apache2-foreground "$@"
fi

exec "$@"

