FROM php:8.2-apache

# Corrige conflito de MPM (More than one MPM loaded)
# Desabilita TODOS os módulos MPM logo no início
RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true

# Habilita explicitamente apenas o mpm_prefork (necessário para mod_php)
RUN a2enmod mpm_prefork rewrite

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get install -y libcurl4-openssl-dev && docker-php-ext-install curl

# Copia todo o projeto para o DocumentRoot do Apache
COPY . /var/www/html/

# Permissões
RUN mkdir -p /var/www/html/sites && chmod -R 755 /var/www/html/sites
RUN chown -R www-data:www-data /var/www/html

# Habilita .htaccess (AllowOverride All)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Limpa o cache de configuração do Apache
RUN rm -rf /var/cache/apache2/*

# Porta do Railway (valor fixo, não depende de variável de ambiente em build time)
RUN echo "Listen 8080" > /etc/apache2/ports.conf && /usr/sbin/apache2ctl configtest || true

EXPOSE 8080

CMD apache2-foreground
