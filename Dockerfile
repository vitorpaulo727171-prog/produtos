FROM php:8.2-apache

# Instalar extensões do PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Copiar arquivos da aplicação
COPY . /var/www/html/

# Definir permissões
RUN chown -R www-data:www-data /var/www/html

# Expor porta
EXPOSE 80

# Comando de inicialização
CMD ["apache2-foreground"]
