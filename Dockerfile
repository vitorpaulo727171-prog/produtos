FROM php:8.2-cli

# Instalar extensões do PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Criar diretório da aplicação
WORKDIR /app

# Copiar arquivos
COPY . /app/

# Expor porta (Render usa $PORT)
EXPOSE 8080

# Iniciar servidor PHP
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
