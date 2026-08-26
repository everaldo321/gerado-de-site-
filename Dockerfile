FROM php:8.2-apache

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get install -y libcurl4-openssl-dev && docker-php-ext-install curl
RUN a2enmod rewrite

# Copia todo o projeto para o DocumentRoot do Apache
COPY . /var/www/html/

# Permissões
RUN mkdir -p /var/www/html/sites && chmod -R 755 /var/www/html/sites
RUN chown -R www-data:www-data /var/www/html

# Habilita .htaccess (AllowOverride All)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Porta do Railway
EXPOSE ${PORT:-8080}

# Apache na porta do Railway
RUN sed -i "s/80/\${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

CMD ["sh", "-c", "sed -i \"s/\${PORT}/$PORT/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground"]
