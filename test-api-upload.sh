#!/bin/bash
# Тест API загрузки фото
# Использование: ./test-api-upload.sh

AD_ID=166
API_URL="http://localhost:8000/ads/${AD_ID}/media"

# 1. Получить информацию о медиа
echo "=== Получение информации о медиа ==="
curl -X GET "${API_URL}/" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest"

# 2. Загрузить тестовое фото (нужен реальный файл)
# echo -e "\n\n=== Загрузка фото ==="
# curl -X POST "${API_URL}/photo" \
#   -H "Accept: application/json" \
#   -H "X-Requested-With: XMLHttpRequest" \
#   -F "photo=@/path/to/test-photo.jpg"