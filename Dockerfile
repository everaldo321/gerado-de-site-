FROM php:8.2-apache

# Corrige conflito de MPM (More than one MPM loaded)
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true
RUN a2enmod mpm_prefork

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
ENV PORT=8080
RUN echo 'Listen ${PORT}' >> /etc/apache2/ports.conf
EXPOSE ${PORT}

CMD apache2-foreground
