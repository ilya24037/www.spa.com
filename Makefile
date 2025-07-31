# Makefile для управления проектом

.PHONY: help install build up down restart logs test deploy

# Цвета для вывода
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

# Переменные
DOCKER_COMPOSE = docker-compose
PHP_CONTAINER = spa-app
COMPOSER = docker exec -it $(PHP_CONTAINER) composer
ARTISAN = docker exec -it $(PHP_CONTAINER) php artisan
NPM = docker exec -it $(PHP_CONTAINER) npm

help: ## Показать эту справку
	@echo ''
	@echo 'Использование:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Установить проект
	@echo "${GREEN}Установка проекта...${RESET}"
	cp .env.example .env
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	$(COMPOSER) install
	$(NPM) install
	$(ARTISAN) key:generate
	$(ARTISAN) migrate
	$(ARTISAN) db:seed
	$(NPM) run build
	@echo "${GREEN}Проект установлен!${RESET}"

build: ## Собрать Docker образы
	$(DOCKER_COMPOSE) build

up: ## Запустить контейнеры
	$(DOCKER_COMPOSE) up -d

down: ## Остановить контейнеры
	$(DOCKER_COMPOSE) down

restart: down up ## Перезапустить контейнеры

logs: ## Показать логи
	$(DOCKER_COMPOSE) logs -f

bash: ## Войти в контейнер приложения
	docker exec -it $(PHP_CONTAINER) sh

mysql: ## Войти в MySQL
	docker exec -it spa-mysql mysql -uroot -p

redis: ## Войти в Redis CLI
	docker exec -it spa-redis redis-cli

test: ## Запустить тесты
	$(ARTISAN) test

test-coverage: ## Запустить тесты с покрытием
	$(ARTISAN) test --coverage

lint: ## Проверить код
	$(COMPOSER) phpcs
	$(COMPOSER) phpstan
	$(NPM) run lint

fix: ## Исправить код
	$(COMPOSER) phpcbf
	$(NPM) run lint:fix

migrate: ## Выполнить миграции
	$(ARTISAN) migrate

migrate-fresh: ## Пересоздать БД и выполнить миграции
	$(ARTISAN) migrate:fresh --seed

cache-clear: ## Очистить кеш
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear

optimize: ## Оптимизировать приложение
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(ARTISAN) event:cache
	$(ARTISAN) app:optimize-performance --all

queue-work: ## Запустить обработчик очередей
	$(ARTISAN) queue:work

schedule-run: ## Запустить планировщик
	$(ARTISAN) schedule:run

backup: ## Создать резервную копию
	@echo "${GREEN}Создание резервной копии...${RESET}"
	mkdir -p backups
	docker exec spa-mysql mysqldump -uroot -p${DB_PASSWORD} ${DB_DATABASE} > backups/db_$(shell date +%Y%m%d_%H%M%S).sql
	tar -czf backups/files_$(shell date +%Y%m%d_%H%M%S).tar.gz storage/app public/uploads
	@echo "${GREEN}Резервная копия создана!${RESET}"

deploy-staging: ## Деплой на staging
	@echo "${GREEN}Деплой на staging...${RESET}"
	git push origin develop
	ssh ${STAGING_USER}@${STAGING_HOST} "cd /var/www/spa-staging && git pull && make optimize"

deploy-production: ## Деплой на production
	@echo "${GREEN}Деплой на production...${RESET}"
	git push origin main
	ssh ${PROD_USER}@${PROD_HOST} "cd /var/www/spa-platform && git pull && make optimize"

monitor: ## Мониторинг производительности
	$(ARTISAN) app:monitor-performance --realtime

report: ## Сгенерировать отчет о производительности
	$(ARTISAN) app:monitor-performance --report