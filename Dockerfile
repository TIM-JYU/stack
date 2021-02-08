# Note: Update PHP version according to supported Moodle version
# Currently Moodle 3.8 supported which supports PHP 7.4
FROM php:7.4-apache AS prod
RUN mkdir -p /var/data/api
RUN chmod a+rw /var/data/api
RUN mkdir -p /var/data/api/stack/logs && mkdir mkdir -p /var/data/api/stack/tmp

# Note: Maxima is not installed locally. Instead, Maxima is provided by a separate service
RUN apt-get update && apt-get install gnuplot libyaml-dev unzip git -y

RUN pecl install yaml
RUN echo "extension=yaml.so" > /usr/local/etc/php/conf.d/yaml.ini
VOLUME ["/var/data/api/stack/plots"]
RUN ln -s /var/data/api/stack/plots /var/www/html/plots
COPY ./ /var/www/html
COPY ./api/config.php.docker /var/www/html/config.php
RUN chmod a+rwx /var/data/api/stack /var/data/api/stack/logs /var/data/api/stack/tmp /var/data/api/stack/plots

ENTRYPOINT  /var/www/html/entrypoint_install_and_run.sh

# Dev target that additionally exposes an SSH server
FROM prod AS dev

ENV APT_INSTALL="DEBIAN_FRONTEND=noninteractive apt-get -qq update && DEBIAN_FRONTEND=noninteractive apt-get -q install --no-install-recommends -y" \
    APT_CLEANUP="rm -rf /var/lib/apt/lists /usr/share/doc ~/.cache /var/cache/oracle-* /var/cache/apk /tmp/*"

USER root
RUN bash -c "${APT_INSTALL} openssh-server && ${APT_CLEANUP}"

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN apt-get update
RUN apt-get install nano -y
RUN apt-get update
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

RUN mkdir /var/run/sshd
RUN echo 1 | pam-auth-update
RUN echo 'root:test' | chpasswd
RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config
EXPOSE 22
CMD service ssh start

ENTRYPOINT  /var/www/html/entrypoint_install_and_run.sh