FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    ffmpeg \
    unzip \
    git \
    curl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . .

CMD ["php", "index.php"]
