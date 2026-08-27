FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# Instala Apache e PHP do zero (evita configs de MPM já habilitadas
# e conflitantes que vêm pré-instaladas na imagem php:8.2-apache)
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    libapache2-mod-php8.3 \
    php8.3 \
    php8.3-curl \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    && rm -rf /var/lib/apt/lists/*

# Garante que apenas o mpm_prefork (exigido pelo mod_php) esteja habilitado
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true
RUN a2enmod mpm_prefork rewrite php8.3

# Copia todo o projeto para o DocumentRoot do Apache
COPY . /var/www/html/

# Permissões
RUN mkdir -p /var/www/html/sites && chmod -R 755 /var/www/html/sites
RUN chown -R www-data:www-data /var/www/html

# Habilita .htaccess (AllowOverride All)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Porta do Railway (valor fixo, não depende de variável de ambiente em build time)
RUN echo "Listen 8080" > /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf && \
    apache2ctl configtest

EXPOSE 8080

CMD ["apache2ctl", "-D", "FOREGROUND"]
