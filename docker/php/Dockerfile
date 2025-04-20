FROM php:8.1-fpm

# システムパッケージとPHP拡張機能のインストール (root ユーザーで実行)
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/* # クリーンアップ

# PHP拡張機能の設定とインストール (root ユーザーで実行)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip

# Composer のインストール (root ユーザーで実行)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Yarn のインストール (root ユーザーで実行、必要であれば)
RUN npm install -g yarn

# 作業ディレクトリを設定
WORKDIR /var/www/html

# デフォルトユーザーを www-data に設定 (コンテナ実行時のデフォルトユーザー)
# アプリケーションファイル設置後などに権限を与える場合に有効
# COPY ./src /var/www/html
# RUN chown -R www-data:www-data /var/www/html
USER www-data

# (必要であれば CMD や ENTRYPOINT を設定)
# CMD ["php-fpm"]