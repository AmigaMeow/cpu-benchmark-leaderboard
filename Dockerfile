FROM php:8.1-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite headers

# 允许 .htaccess 生效
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html

# 用模板生成运行配置（环境变量注入，compose 里配置）
RUN cp config/database.example.php config/database.php \
 && cp config/site.example.php config/site.php

EXPOSE 80
