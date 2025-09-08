# 📦 Руководство по установке и настройке

## Содержание
1. [Требования](#требования)
2. [Получение API ключа](#получение-api-ключа)
3. [Установка компонентов](#установка-компонентов)
4. [Настройка проекта](#настройка-проекта)
5. [Проверка работоспособности](#проверка-работоспособности)

## 📋 Требования

### Минимальные требования
- **Node.js**: версия 14.0 или выше
- **Браузер**: Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- **Yandex Maps API ключ**: обязательно

### Для Vue 3 проектов
- **Vue**: версия 3.2 или выше
- **TypeScript**: версия 4.5 или выше (опционально)

### Для React проектов
- **React**: версия 17.0 или выше
- **TypeScript**: версия 4.5 или выше (опционально)

## 🔑 Получение API ключа

### Шаг 1: Регистрация в Яндекс
1. Перейдите на [developer.tech.yandex.ru](https://developer.tech.yandex.ru/)
2. Войдите в свой аккаунт Яндекс или создайте новый
3. Перейдите в раздел "JavaScript API и HTTP Геокодер"

### Шаг 2: Создание ключа
1. Нажмите "Получить ключ"
2. Заполните форму:
   - **Название**: название вашего проекта
   - **Описание**: краткое описание использования
   - **Домены**: укажите домены где будет использоваться карта
     - Для разработки: `localhost`, `127.0.0.1`
     - Для продакшена: ваш домен, например `example.com`
3. Согласитесь с условиями использования
4. Нажмите "Создать"

### Шаг 3: Сохранение ключа
```javascript
// Сохраните ключ в переменной окружения
// .env
VITE_YANDEX_MAPS_API_KEY=ваш_ключ_здесь

// Или в конфигурационном файле
// config/maps.js
export const YANDEX_MAPS_CONFIG = {
  apiKey: 'ваш_ключ_здесь',
  lang: 'ru_RU'
}
```

## 📥 Установка компонентов

### Вариант 1: Копирование файлов

```bash
# 1. Клонируйте или скачайте компоненты
git clone https://github.com/your-repo/ymaps-components.git

# 2. Скопируйте папку в ваш проект
cp -r ymaps-components /путь/к/вашему/проекту/src/

# 3. Структура должна выглядеть так:
your-project/
├── src/
│   ├── ymaps-components/
│   │   ├── core/
│   │   ├── modules/
│   │   ├── behaviors/
│   │   └── examples/
│   └── ...
└── ...
```

### Вариант 2: NPM пакет (если опубликован)

```bash
# Установка через npm
npm install @spa-platform/ymaps-components

# Или через yarn
yarn add @spa-platform/ymaps-components
```

### Вариант 3: Git подмодуль

```bash
# Добавление как подмодуль
git submodule add https://github.com/your-repo/ymaps-components.git src/ymaps-components

# Инициализация подмодуля
git submodule update --init --recursive
```

## ⚙️ Настройка проекта

### Для Vanilla JavaScript

```html
<!-- index.html -->
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Yandex Maps Example</title>
  <style>
    #map {
      width: 100%;
      height: 400px;
    }
  </style>
</head>
<body>
  <div id="map"></div>
  
  <script type="module">
    import YMapsCore from './src/ymaps-components/core/YMapsCore.js'
    
    async function init() {
      const mapsCore = new YMapsCore({
        apiKey: 'YOUR_API_KEY'
      })
      
      await mapsCore.loadAPI()
      const map = await mapsCore.createMap('map')
    }
    
    init()
  </script>
</body>
</html>
```

### Для Vue 3 + Vite

```javascript
// vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
      '@ymaps': path.resolve(__dirname, 'src/ymaps-components')
    }
  }
})
```

```javascript
// main.js
import { createApp } from 'vue'
import App from './App.vue'

// Глобальная конфигурация (опционально)
window.YANDEX_MAPS_CONFIG = {
  apiKey: import.meta.env.VITE_YANDEX_MAPS_API_KEY
}

createApp(App).mount('#app')
```

```vue
<!-- App.vue -->
<template>
  <div id="app">
    <MapComponent />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import YMapsCore from '@ymaps/core/YMapsCore'

onMounted(async () => {
  const mapsCore = new YMapsCore({
    apiKey: import.meta.env.VITE_YANDEX_MAPS_API_KEY
  })
  
  await mapsCore.loadAPI()
  console.log('Yandex Maps API загружен')
})
</script>
```

### Для React

```javascript
// webpack.config.js или craco.config.js
module.exports = {
  webpack: {
    alias: {
      '@ymaps': path.resolve(__dirname, 'src/ymaps-components')
    }
  }
}
```

```javascript
// App.jsx
import React, { useEffect, useRef } from 'react'
import YMapsCore from '@ymaps/core/YMapsCore'

function App() {
  const mapRef = useRef(null)
  const mapInstanceRef = useRef(null)
  
  useEffect(() => {
    const initMap = async () => {
      const mapsCore = new YMapsCore({
        apiKey: process.env.REACT_APP_YANDEX_MAPS_API_KEY
      })
      
      await mapsCore.loadAPI()
      
      if (mapRef.current) {
        const map = await mapsCore.createMap(mapRef.current)
        mapInstanceRef.current = map
      }
    }
    
    initMap()
    
    return () => {
      if (mapInstanceRef.current) {
        // Очистка при размонтировании
      }
    }
  }, [])
  
  return <div ref={mapRef} style={{ width: '100%', height: '400px' }} />
}

export default App
```

### TypeScript настройка

```json
// tsconfig.json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      "@ymaps/*": ["src/ymaps-components/*"]
    },
    "types": ["vite/client"],
    "moduleResolution": "node",
    "allowJs": true
  },
  "include": [
    "src/**/*",
    "src/ymaps-components/**/*.d.ts"
  ]
}
```

## ✅ Проверка работоспособности

### Тестовый скрипт

Создайте файл `test-map.html`:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Тест Yandex Maps Components</title>
  <style>
    body {
      margin: 0;
      padding: 20px;
      font-family: Arial, sans-serif;
    }
    #map {
      width: 100%;
      height: 400px;
      border: 2px solid #ccc;
      margin-bottom: 20px;
    }
    .status {
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 10px;
    }
    .success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .info {
      background: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }
  </style>
</head>
<body>
  <h1>Тестирование Yandex Maps Components</h1>
  
  <div id="status-container"></div>
  <div id="map"></div>
  
  <script type="module">
    import YMapsCore from './ymaps-components/core/YMapsCore.js'
    import Placemark from './ymaps-components/modules/Placemark/Placemark.js'
    import MapBehaviors from './ymaps-components/behaviors/MapBehaviors.js'
    
    const statusContainer = document.getElementById('status-container')
    
    function addStatus(message, type = 'info') {
      const div = document.createElement('div')
      div.className = `status ${type}`
      div.textContent = `${new Date().toLocaleTimeString()}: ${message}`
      statusContainer.appendChild(div)
    }
    
    async function testComponents() {
      try {
        // Тест 1: Инициализация ядра
        addStatus('Тест 1: Инициализация YMapsCore...', 'info')
        const mapsCore = new YMapsCore({
          apiKey: 'YOUR_API_KEY', // Замените на ваш ключ
          lang: 'ru_RU'
        })
        addStatus('✓ YMapsCore создан', 'success')
        
        // Тест 2: Загрузка API
        addStatus('Тест 2: Загрузка Yandex Maps API...', 'info')
        await mapsCore.loadAPI()
        addStatus('✓ API загружен', 'success')
        
        // Тест 3: Создание карты
        addStatus('Тест 3: Создание карты...', 'info')
        const map = await mapsCore.createMap('map', {
          center: [55.753994, 37.622093],
          zoom: 10,
          controls: ['zoomControl']
        })
        addStatus('✓ Карта создана', 'success')
        
        // Тест 4: Добавление метки
        addStatus('Тест 4: Добавление метки...', 'info')
        const placemark = new Placemark(
          [55.753994, 37.622093],
          { 
            balloonContent: 'Тестовая метка',
            hintContent: 'Нажмите для информации'
          },
          { preset: 'islands#redIcon' }
        )
        await placemark.addToMap(map)
        addStatus('✓ Метка добавлена', 'success')
        
        // Тест 5: Менеджер поведений
        addStatus('Тест 5: Создание менеджера поведений...', 'info')
        const behaviors = new MapBehaviors(map, {
          drag: true,
          scrollZoom: true
        })
        addStatus('✓ Менеджер поведений создан', 'success')
        
        addStatus('🎉 Все тесты пройдены успешно!', 'success')
        
      } catch (error) {
        addStatus(`❌ Ошибка: ${error.message}`, 'error')
        console.error(error)
      }
    }
    
    // Запуск тестов
    testComponents()
  </script>
</body>
</html>
```

### Проверочный чек-лист

- [ ] API ключ получен и добавлен в конфигурацию
- [ ] Компоненты скопированы в проект
- [ ] Пути импорта настроены правильно
- [ ] Тестовая страница открывается без ошибок
- [ ] Карта отображается корректно
- [ ] Метки добавляются на карту
- [ ] Перетаскивание и зум работают
- [ ] Консоль браузера не содержит ошибок

## 🔧 Решение проблем

### Ошибка: "API key is invalid"
```javascript
// Проверьте правильность ключа
const mapsCore = new YMapsCore({
  apiKey: 'ваш_действительный_ключ'
})

// Проверьте домены в настройках ключа
// Должны включать localhost для разработки
```

### Ошибка: "Cannot find module"
```javascript
// Проверьте пути импорта
// Правильно:
import YMapsCore from '@/ymaps-components/core/YMapsCore.js'

// Неправильно:
import YMapsCore from 'ymaps-components/core/YMapsCore' // без .js
```

### Карта не отображается
```css
/* Убедитесь, что контейнер имеет размеры */
#map {
  width: 100%;
  height: 400px; /* Обязательно задайте высоту */
}
```

### Ошибки CORS
```javascript
// Для локальной разработки используйте dev server
// Vite
npm run dev

// Create React App
npm start

// Или настройте CORS на сервере
```

## 📚 Дополнительные ресурсы

- [Документация Yandex Maps API](https://yandex.ru/dev/maps/jsapi/doc/)
- [Примеры использования](./examples/)
- [API Reference](./README.md#api-reference)

---

<div align="center">
  <strong>Нужна помощь?</strong><br>
  Создайте issue в репозитории или напишите на support@spa-platform.ru
</div>