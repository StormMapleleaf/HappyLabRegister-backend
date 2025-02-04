FROM php:8.2-fpm

RUN touch /etc/apt/sources.list
# 使用国内APT镜像源（阿里云）
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list \
    && sed -i 's/security.debian.org/mirrors.aliyun.com\/debian-security/g' /etc/apt/sources.list

# 安装系统依赖
RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev libpq-dev zip unzip \
    # 安装编译Redis扩展需要的临时依赖
    autoconf build-essential \
    # 配置并安装PHP扩展
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql \
    # 安装Redis扩展
    && pecl install redis \
    && docker-php-ext-enable redis \
    # 清理工作
    && apt-get purge -y --auto-remove autoconf build-essential \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 设置工作目录
WORKDIR /var/www

# 复制 Laravel 项目到容器中
COPY . .

# 安装Composer并使用国内镜像
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 设置文件权限
RUN chown -R www-data:www-data /var/www

# 暴露 PHP-FPM 端口
EXPOSE 9000

# 启动 PHP-FPM
CMD ["php-fpm"]