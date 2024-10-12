FROM gitlab.sunadv.ru:5050/development/l.r.club:base
ENV TZ=Europe/Moscow
RUN a2enmod proxy proxy_http
COPY . /var/www/html

RUN cd /var/www/html/_backend && composer install --no-scripts -n
RUN cd /var/www/html/_frontend && npm install
RUN cd /var/www/html/_frontend && npm run eslint
RUN cd /var/www/html/_frontend && npm run build

RUN mkdir /var/www/html/_backend/var
RUN chmod -R 0777 /var/www/html/_backend/var

COPY ./docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf
COPY ./docker/timezone.ini /usr/local/etc/php/conf.d/timezone.ini
COPY ./docker/supervisord.conf /etc/supervisor/supervisord.conf

WORKDIR /var/www/html/_resources/public

CMD ["/bin/bash", "-c", "apache2-foreground & supervisord -c /etc/supervisor/supervisord.conf"]
