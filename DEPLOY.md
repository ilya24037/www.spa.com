# 🚀 Deploy Guide

> Полное руководство по деплою SPA Platform в продакшн

[![Docker](https://img.shields.io/badge/Docker-24.0-blue.svg)](https://docker.com)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Nginx](https://img.shields.io/badge/Nginx-1.24-green.svg)](https://nginx.org)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://mysql.com)

## 📋 Содержание

- [Обзор деплоя](#-обзор-деплоя)
- [Предварительные требования](#-предварительные-требования)
- [Подготовка к деплою](#-подготовка-к-деплою)
- [Настройка сервера](#-настройка-сервера)
- [Docker деплой](#-docker-деплой)
- [CI/CD Pipeline](#-cicd-pipeline)
- [Мониторинг](#-мониторинг)
- [Безопасность](#-безопасность)
- [Rollback процедуры](#-rollback-процедуры)
- [Troubleshooting](#-troubleshooting)
- [Чек-лист деплоя](#-чек-лист-деплоя)

---

## 🌐 Обзор деплоя

**SPA Platform** поддерживает несколько способов деплоя для разных окружений и требований.

### Окружения:
- **Development** - локальная разработка
- **Staging** - тестовая среда
- **Production** - продакшн

### Способы деплоя:
- **Docker** - контейнеризованный деплой
- **Traditional** - классический деплой
- **Cloud** - облачный деплой (AWS, DigitalOcean, etc.)

### Архитектура продакшн:
```
Internet → Load Balancer → Nginx → PHP-FPM → Laravel
                    ↓
                MySQL + Redis
                    ↓
                File Storage (S3/CDN)
```

---

## ⚙️ Предварительные требования

### **Минимальные требования сервера:**

#### **Production:**
- **CPU**: 4 cores (2.4 GHz)
- **RAM**: 8 GB
- **Storage**: 100 GB SSD
- **Network**: 1 Gbps
- **OS**: Ubuntu 22.04 LTS

#### **Staging:**
- **CPU**: 2 cores (2.0 GHz)
- **RAM**: 4 GB
- **Storage**: 50 GB SSD
- **Network**: 100 Mbps
- **OS**: Ubuntu 22.04 LTS

### **Программное обеспечение:**
- **Docker** 24.0+
- **Docker Compose** 2.0+
- **Git** 2.30+
- **Node.js** 18+ (для сборки)
- **Nginx** 1.24+ (если не используем Docker)

### **Внешние сервисы:**
- **Domain** - доменное имя
- **SSL Certificate** - Let's Encrypt или коммерческий
- **CDN** - Cloudflare или AWS CloudFront
- **Monitoring** - Sentry, New Relic
- **Email** - SMTP сервер или SendGrid

---

## 🔧 Подготовка к деплою

### **1. Настройка репозитория:**

#### **Создание production ветки:**
```bash
# Создаем production ветку
git checkout -b production
git push origin production

# Настраиваем защиту ветки
# Settings → Branches → Add rule
# Branch name pattern: production
# Require pull request reviews: 1
# Require status checks: true
```

#### **Настройка GitHub Actions:**
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [production]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Deploy to server
        run: |
          # Деплой скрипт
```

### **2. Настройка переменных окружения:**

#### **Production .env:**
```env
APP_NAME="SPA Platform"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://spa-platform.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=spa_platform_prod
DB_USERNAME=spa_user
DB_PASSWORD=secure_password

REDIS_HOST=redis
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls

AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=spa-platform-storage

SENTRY_LARAVEL_DSN=your-sentry-dsn
NEW_RELIC_LICENSE_KEY=your-newrelic-key
```

### **3. Настройка Docker:**

#### **Dockerfile:**
```dockerfile
FROM php:8.3-fpm

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor

# Устанавливаем PHP расширения
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Рабочая директория
WORKDIR /var/www

# Копируем код
COPY . .

# Устанавливаем зависимости
RUN composer install --optimize-autoloader --no-dev
RUN npm ci --production

# Собираем фронтенд
RUN npm run build

# Настраиваем права
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Экспонируем порт
EXPOSE 80

# Запускаем приложение
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

#### **docker-compose.prod.yml:**
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/storage
      - ./bootstrap/cache:/var/www/bootstrap/cache
    depends_on:
      - mysql
      - redis
    restart: unless-stopped

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: spa_platform_prod
      MYSQL_USER: spa_user
      MYSQL_PASSWORD: secure_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/init:/docker-entrypoint-initdb.d
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --requirepass redis_password
    volumes:
      - redis_data:/data
    restart: unless-stopped

  nginx:
    image: nginx:alpine
    ports:
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app
    restart: unless-stopped

volumes:
  mysql_data:
  redis_data:
```

---

## 🖥️ Настройка сервера

### **1. Подготовка сервера:**

#### **Обновление системы:**
```bash
# Обновляем систему
sudo apt update && sudo apt upgrade -y

# Устанавливаем необходимые пакеты
sudo apt install -y curl wget git unzip software-properties-common

# Устанавливаем Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Устанавливаем Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

#### **Создание пользователя для деплоя:**
```bash
# Создаем пользователя
sudo adduser deployer
sudo usermod -aG docker deployer
sudo usermod -aG www-data deployer

# Настраиваем SSH ключи
sudo mkdir -p /home/deployer/.ssh
sudo cp ~/.ssh/authorized_keys /home/deployer/.ssh/
sudo chown -R deployer:deployer /home/deployer/.ssh
sudo chmod 700 /home/deployer/.ssh
sudo chmod 600 /home/deployer/.ssh/authorized_keys
```

### **2. Настройка Nginx:**

#### **nginx.conf:**
```nginx
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Логирование
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;

    # Основные настройки
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # Gzip сжатие
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    # Upstream для PHP-FPM
    upstream php-fpm {
        server app:9000;
    }

    # HTTP → HTTPS редирект
    server {
        listen 80;
        server_name spa-platform.com www.spa-platform.com;
        return 301 https://$server_name$request_uri;
    }

    # HTTPS сервер
    server {
        listen 443 ssl http2;
        server_name spa-platform.com www.spa-platform.com;

        # SSL настройки
        ssl_certificate /etc/nginx/ssl/cert.pem;
        ssl_certificate_key /etc/nginx/ssl/key.pem;
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
        ssl_prefer_server_ciphers off;

        # Безопасность
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-XSS-Protection "1; mode=block" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header Referrer-Policy "no-referrer-when-downgrade" always;
        add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

        # Корневая директория
        root /var/www/public;
        index index.php;

        # Основные location блоки
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # PHP обработка
        location ~ \.php$ {
            fastcgi_pass php-fpm;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }

        # Статические файлы
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }

        # API rate limiting
        location /api/ {
            limit_req zone=api burst=20 nodelay;
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Login rate limiting
        location /api/auth/login {
            limit_req zone=login burst=5 nodelay;
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Запрет доступа к системным файлам
        location ~ /\. {
            deny all;
        }

        location ~ /(storage|bootstrap/cache) {
            deny all;
        }
    }
}
```

### **3. Настройка SSL:**

#### **Let's Encrypt:**
```bash
# Устанавливаем Certbot
sudo apt install certbot python3-certbot-nginx

# Получаем сертификат
sudo certbot --nginx -d spa-platform.com -d www.spa-platform.com

# Настраиваем автообновление
sudo crontab -e
# Добавляем: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🐳 Docker деплой

### **1. Подготовка к деплою:**

#### **Скрипт деплоя (deploy.sh):**
```bash
#!/bin/bash

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция логирования
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

# Проверяем, что мы в правильной директории
if [ ! -f "docker-compose.prod.yml" ]; then
    error "docker-compose.prod.yml не найден. Запустите скрипт из корня проекта."
fi

# Проверяем, что .env.production существует
if [ ! -f ".env.production" ]; then
    error ".env.production не найден. Создайте файл с настройками продакшн."
fi

log "Начинаем деплой SPA Platform..."

# Останавливаем текущие контейнеры
log "Останавливаем текущие контейнеры..."
docker-compose -f docker-compose.prod.yml down

# Создаем backup базы данных
log "Создаем backup базы данных..."
if [ -f "backup.sql" ]; then
    mv backup.sql backup_$(date +%Y%m%d_%H%M%S).sql
fi

# Получаем последние изменения
log "Получаем последние изменения из Git..."
git pull origin production

# Копируем production .env
log "Настраиваем переменные окружения..."
cp .env.production .env

# Собираем Docker образы
log "Собираем Docker образы..."
docker-compose -f docker-compose.prod.yml build --no-cache

# Запускаем контейнеры
log "Запускаем контейнеры..."
docker-compose -f docker-compose.prod.yml up -d

# Ждем запуска базы данных
log "Ждем запуска базы данных..."
sleep 30

# Выполняем миграции
log "Выполняем миграции базы данных..."
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Кэшируем конфигурацию
log "Кэшируем конфигурацию..."
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# Очищаем кэш
log "Очищаем кэш..."
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear

# Проверяем статус контейнеров
log "Проверяем статус контейнеров..."
docker-compose -f docker-compose.prod.yml ps

# Проверяем доступность приложения
log "Проверяем доступность приложения..."
sleep 10
if curl -f http://localhost/health > /dev/null 2>&1; then
    log "✅ Деплой успешно завершен!"
    log "Приложение доступно по адресу: https://spa-platform.com"
else
    error "❌ Приложение недоступно после деплоя"
fi

log "Деплой завершен!"
```

#### **Запуск деплоя:**
```bash
# Делаем скрипт исполняемым
chmod +x deploy.sh

# Запускаем деплой
./deploy.sh
```

### **2. Мониторинг контейнеров:**

#### **Проверка статуса:**
```bash
# Статус контейнеров
docker-compose -f docker-compose.prod.yml ps

# Логи приложения
docker-compose -f docker-compose.prod.yml logs -f app

# Логи базы данных
docker-compose -f docker-compose.prod.yml logs -f mysql

# Использование ресурсов
docker stats
```

#### **Перезапуск сервисов:**
```bash
# Перезапуск приложения
docker-compose -f docker-compose.prod.yml restart app

# Перезапуск всех сервисов
docker-compose -f docker-compose.prod.yml restart

# Обновление конфигурации
docker-compose -f docker-compose.prod.yml up -d --force-recreate
```

---

## 🔄 CI/CD Pipeline

### **1. GitHub Actions:**

#### **.github/workflows/deploy.yml:**
```yaml
name: Deploy to Production

on:
  push:
    branches: [production]
  workflow_dispatch:

env:
  DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
  DEPLOY_USER: ${{ secrets.DEPLOY_USER }}
  DEPLOY_KEY: ${{ secrets.DEPLOY_KEY }}

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, dom, fileinfo, mysql, zip
          
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
        
      - name: Run tests
        run: ./vendor/bin/pest

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/production'
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup SSH
        uses: webfactory/ssh-agent@v0.7.0
        with:
          ssh-private-key: ${{ secrets.DEPLOY_KEY }}
          
      - name: Deploy to server
        run: |
          ssh -o StrictHostKeyChecking=no ${{ env.DEPLOY_USER }}@${{ env.DEPLOY_HOST }} '
            cd /var/www/spa-platform &&
            git pull origin production &&
            ./deploy.sh
          '
          
      - name: Notify deployment
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          channel: '#deployments'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### **2. Настройка секретов:**

#### **GitHub Secrets:**
```bash
DEPLOY_HOST=your-server-ip
DEPLOY_USER=deployer
DEPLOY_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
SLACK_WEBHOOK=https://hooks.slack.com/services/...
```

### **3. Автоматический деплой:**

#### **Webhook для автоматического деплоя:**
```php
// routes/web.php
Route::post('/deploy', function (Request $request) {
    // Проверяем подпись GitHub
    $signature = $request->header('X-Hub-Signature-256');
    $payload = $request->getContent();
    $secret = config('app.github_webhook_secret');
    
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($signature, $expectedSignature)) {
        abort(401, 'Invalid signature');
    }
    
    // Выполняем деплой
    $output = shell_exec('cd /var/www/spa-platform && ./deploy.sh 2>&1');
    
    return response()->json([
        'success' => true,
        'output' => $output
    ]);
});
```

---

## 📊 Мониторинг

### **1. Настройка Sentry:**

#### **Установка:**
```bash
composer require sentry/sentry-laravel
```

#### **Конфигурация:**
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('APP_ENV'),
    'sample_rate' => 1.0,
    'traces_sample_rate' => 1.0,
];
```

### **2. Настройка New Relic:**

#### **Установка:**
```bash
# Устанавливаем New Relic PHP agent
curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-10.0.0.0-linux.tar.gz | tar -C /tmp -zx
sudo /tmp/newrelic-php5-10.0.0.0-linux/newrelic-install install

# Настраиваем
sudo nano /etc/newrelic/newrelic.cfg
```

#### **Конфигурация:**
```ini
newrelic.license = "your-license-key"
newrelic.appname = "SPA Platform"
newrelic.distributed_tracing_enabled = true
```

### **3. Логирование:**

#### **Настройка логов:**
```php
// config/logging.php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

#### **Мониторинг логов:**
```bash
# Просмотр логов в реальном времени
tail -f storage/logs/laravel.log

# Поиск ошибок
grep "ERROR" storage/logs/laravel.log

# Анализ логов
grep "CRITICAL" storage/logs/laravel.log | tail -10
```

---

## 🔒 Безопасность

### **1. Настройка файрвола:**

#### **UFW:**
```bash
# Устанавливаем UFW
sudo apt install ufw

# Настраиваем правила
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Включаем файрвол
sudo ufw enable
```

### **2. Настройка fail2ban:**

#### **Установка:**
```bash
sudo apt install fail2ban

# Создаем конфигурацию
sudo nano /etc/fail2ban/jail.local
```

#### **Конфигурация:**
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 3
```

### **3. Регулярные обновления:**

#### **Автоматические обновления:**
```bash
# Устанавливаем unattended-upgrades
sudo apt install unattended-upgrades

# Настраиваем
sudo dpkg-reconfigure -plow unattended-upgrades

# Проверяем статус
sudo unattended-upgrade --dry-run
```

---

## 🔄 Rollback процедуры

### **1. Быстрый rollback:**

#### **Скрипт rollback (rollback.sh):**
```bash
#!/bin/bash

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

# Проверяем аргументы
if [ $# -eq 0 ]; then
    error "Укажите хеш коммита для отката"
fi

COMMIT_HASH=$1

log "Начинаем откат к коммиту: $COMMIT_HASH"

# Создаем backup текущего состояния
log "Создаем backup текущего состояния..."
docker-compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD spa_platform_prod > backup_before_rollback_$(date +%Y%m%d_%H%M%S).sql

# Останавливаем контейнеры
log "Останавливаем контейнеры..."
docker-compose -f docker-compose.prod.yml down

# Откатываемся к указанному коммиту
log "Откатываемся к коммиту $COMMIT_HASH..."
git reset --hard $COMMIT_HASH

# Перезапускаем контейнеры
log "Перезапускаем контейнеры..."
docker-compose -f docker-compose.prod.yml up -d

# Ждем запуска
sleep 30

# Выполняем миграции
log "Выполняем миграции..."
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Проверяем доступность
log "Проверяем доступность приложения..."
if curl -f http://localhost/health > /dev/null 2>&1; then
    log "✅ Откат успешно завершен!"
else
    error "❌ Приложение недоступно после отката"
fi

log "Откат завершен!"
```

#### **Использование:**
```bash
# Откат к последнему коммиту
./rollback.sh HEAD~1

# Откат к конкретному коммиту
./rollback.sh abc123def456
```

### **2. Восстановление из backup:**

#### **Скрипт восстановления (restore.sh):**
```bash
#!/bin/bash

# Проверяем аргументы
if [ $# -eq 0 ]; then
    echo "Укажите файл backup для восстановления"
    exit 1
fi

BACKUP_FILE=$1

log "Восстанавливаем из backup: $BACKUP_FILE"

# Останавливаем контейнеры
docker-compose -f docker-compose.prod.yml down

# Восстанавливаем базу данных
log "Восстанавливаем базу данных..."
docker-compose -f docker-compose.prod.yml up -d mysql
sleep 30
docker-compose -f docker-compose.prod.yml exec mysql mysql -u root -p$MYSQL_ROOT_PASSWORD spa_platform_prod < $BACKUP_FILE

# Запускаем все контейнеры
log "Запускаем все контейнеры..."
docker-compose -f docker-compose.prod.yml up -d

log "✅ Восстановление завершено!"
```

---

## 🔧 Troubleshooting

### **Частые проблемы:**

#### **1. Приложение не запускается:**
```bash
# Проверяем логи
docker-compose -f docker-compose.prod.yml logs app

# Проверяем статус контейнеров
docker-compose -f docker-compose.prod.yml ps

# Проверяем переменные окружения
docker-compose -f docker-compose.prod.yml exec app env | grep APP_
```

#### **2. Ошибки базы данных:**
```bash
# Проверяем подключение к БД
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
# В tinker: DB::connection()->getPdo();

# Проверяем миграции
docker-compose -f docker-compose.prod.yml exec app php artisan migrate:status
```

#### **3. Проблемы с правами доступа:**
```bash
# Исправляем права
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

#### **4. Высокая нагрузка:**
```bash
# Проверяем использование ресурсов
docker stats

# Проверяем логи Nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### **Полезные команды:**

#### **Диагностика:**
```bash
# Проверка здоровья приложения
curl -f http://localhost/health

# Проверка API
curl -f http://localhost/api/health

# Проверка базы данных
docker-compose -f docker-compose.prod.yml exec mysql mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW DATABASES;"

# Проверка Redis
docker-compose -f docker-compose.prod.yml exec redis redis-cli ping
```

---

## ✅ Чек-лист деплоя

### **Перед деплоем:**
- [ ] Все тесты проходят
- [ ] Код проверен в code review
- [ ] Документация обновлена
- [ ] Backup базы данных создан
- [ ] Переменные окружения настроены
- [ ] SSL сертификаты действительны
- [ ] Мониторинг настроен

### **Во время деплоя:**
- [ ] Остановлены текущие контейнеры
- [ ] Получен последний код
- [ ] Выполнены миграции
- [ ] Кэш очищен
- [ ] Контейнеры запущены
- [ ] Проверена доступность

### **После деплоя:**
- [ ] Приложение доступно
- [ ] API работает
- [ ] База данных подключена
- [ ] Логи не содержат ошибок
- [ ] Мониторинг активен
- [ ] Уведомления отправлены

### **В случае проблем:**
- [ ] Проверены логи
- [ ] Выполнен rollback
- [ ] Команда уведомлена
- [ ] Проблема документирована
- [ ] План исправления создан

---

## 📞 Поддержка

### **Контакты:**
- **Email**: devops@spa-platform.com
- **Slack**: #devops
- **GitHub**: Issues для багов

### **Документация:**
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Nginx Configuration](https://nginx.org/en/docs/)

---

**Последнее обновление**: {{ date('Y-m-d') }}
**Версия документа**: 1.0.0
**Автор**: Команда DevOps SPA Platform