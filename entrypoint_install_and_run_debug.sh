service ssh start
php /var/www/html/api/install.php
echo 'zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20151012/xdebug.so\n\
[XDebug]\n\
xdebug.client_host = host.docker.internal\n\
xdebug.mode = debug\n\
xdebug.start_with_request = yes\n\' >>/usr/local/etc/php/php.ini

sed -i '3ichmod a+rwx /var/data/api/stack /var/data/api/stack/logs /var/data/api/stack/tmp /var/data/api/stack/plots' /usr/local/bin/docker-php-entrypoint
echo "<?php phpinfo(); ?>" >phpinfo.php
. /etc/apache2/envvars
apache2 -D FOREGROUND