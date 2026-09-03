FROM php:8.2-cli

# Install SQLite3 and Curl development extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_sqlite sqlite3 curl

WORKDIR /app

COPY . /app

RUN chmod +x start.sh

EXPOSE 8080

CMD ["./start.sh"]
