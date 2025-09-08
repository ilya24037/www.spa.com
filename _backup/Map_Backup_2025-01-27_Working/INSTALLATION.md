# 🗺️ Установка карты из бекапа

## 📋 Быстрая установка

### Windows:
```bash
cd _backup/Map_Backup_2025-01-27_Working
restore_map.bat
```

### Linux/Mac:
```bash
cd _backup/Map_Backup_2025-01-27_Working
chmod +x restore_map.sh
./restore_map.sh
```

## 🔧 Ручная установка

### 1. Установка зависимостей
```bash
npm install vue-yandex-maps@^2.2.1
```

### 2. Копирование файлов
```bash
# Основной компонент карты
cp VueYandexMap.vue resources/js/src/shared/ui/molecules/VueYandexMap/VueYandexMap.vue

# Интеграция в формы
cp GeoSection.vue resources/js/src/features/AdSections/GeoSection/ui/GeoSection.vue

# TypeScript типы
cp types_index.ts resources/js/src/features/map/types/index.ts
```

### 3. Настройка app.js
Добавьте в `resources/js/app.js`:

```javascript
// Импорт
import { createYmaps } from 'vue-yandex-maps';

// В создании приложения
.use(createYmaps({
    apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
    lang: 'ru_RU'
}))
```

### 4. Запуск
```bash
npm run dev
```

## ✅ Проверка работы

1. Откройте http://spa.test/additem
2. Перейдите в секцию "География"
3. Проверьте:
   - ✅ Поиск адреса работает
   - ✅ Подсказки появляются
   - ✅ Карта перемещается при выборе подсказки
   - ✅ Клик по карте работает
   - ✅ Обратный геокодинг работает

## 🚨 Устранение проблем

### Карта не отображается:
- Проверьте API ключ в app.js
- Проверьте консоль браузера на ошибки
- Убедитесь что vue-yandex-maps установлен

### Поиск не работает:
- Проверьте API ключ Yandex
- Проверьте сетевое соединение
- Проверьте CORS настройки

### Ошибки TypeScript:
- Убедитесь что types_index.ts скопирован
- Перезапустите TypeScript сервер

## 📞 Поддержка

Если что-то не работает:
1. Проверьте консоль браузера
2. Проверьте консоль Laravel
3. Убедитесь что все файлы скопированы
4. Проверьте настройки в app.js

---
**Версия:** vue-yandex-maps 2.2.1  
**Дата:** 27.01.2025  
**Статус:** Работает стабильно
