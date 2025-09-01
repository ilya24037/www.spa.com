# 🐛 РУКОВОДСТВО ПО ОТЛАДКЕ КАРТЫ

**Дата:** 29.08.2025  
**Задача:** Диагностика проблем с загрузкой карты

## 🚀 ДОБАВЛЕННОЕ ЛОГИРОВАНИЕ

### **1. MapLoader (Загрузка API)**
```
[MapLoader] 🚀 Начинаем загрузку Yandex Maps API
[MapLoader] 🔑 API Key: 23ff8acc-835f-4e99-8b19-d33c5d346e18
[MapLoader] 📡 Загружаем API с URL: https://api-maps.yandex.ru/...
[MapLoader] 📦 Script загружен, ждём ymaps.ready()
[MapLoader] ✅ Yandex Maps API готов к использованию
```

### **2. MapCore (Создание карты)**
```
[MapCore] 🚀 Начинаем инициализацию карты
[MapCore] 📋 Параметры: {mapId, center, zoom, apiKey}
[MapCore] 📦 DOM контейнер найден
[MapCore] ✅ Yandex Maps API загружен успешно
[MapCore] ⏱️ Ждём рендеринга DOM (100ms)
[MapCore] 🗺️ Создаём карту с конфигурацией
[MapCore] ✅ Карта создана
[MapCore] 🔌 Подключаем плагины
[MapCore] ✅ Карта готова к использованию!
```

### **3. MapContainer (Плагины)**
```
[MapContainer] 🎉 Получен сигнал ready от MapCore
[MapContainer] ✅ MapCore найден, подключаем плагины
[MapContainer] 🔌 Подключаем MarkersPlugin (mode: multiple)
[MapContainer] 📡 Подписываемся на события store
[MapContainer] ✅ Все плагины подключены, эмитируем ready
```

### **4. YandexMap (Адаптер)**
```
[YandexMap] 🚀 YandexMap компонент смонтирован
[YandexMap] 📋 Полученные props: {mode, height, center}
[YandexMap] 📍 Вычисленный центр: {lat: 55.7558, lng: 37.6176}
[YandexMap] 🎉 handleReady вызван с картой
```

### **5. Диагностика (Автоматическая)**
```
[MapDiagnostics] 🚀 Начинаем полную диагностику
[MapDiagnostics] ✅ Scripts: Скрипт Yandex Maps загружен
[MapDiagnostics] ✅ API Availability: Yandex Maps API загружен и готов
[MapDiagnostics] ✅ DOM Container: Контейнер найден
[MapDiagnostics] ✅ Map Config: Конфигурация карты корректна
[MapDiagnostics] ✅ Map Instance: Карта успешно создана
[MapDiagnostics] 📊 ИТОГИ: ✅ Успешно: 5, ⚠️ Предупреждения: 0, ❌ Ошибки: 0
```

## 🔍 КАК ИСПОЛЬЗОВАТЬ ЛОГИРОВАНИЕ

### **Шаг 1: Откройте консоль браузера**
1. Перейдите на страницу с картой (`http://spa.test`)
2. Нажмите `F12` или `Ctrl+Shift+I`
3. Откройте вкладку **Console**

### **Шаг 2: Перезагрузите страницу**
1. Нажмите `Ctrl+F5` для полной перезагрузки
2. Следите за логами в консоли

### **Шаг 3: Анализируйте логи**

#### **Нормальная загрузка:**
```
[MapLoader] 🚀 Начинаем загрузку...
[MapLoader] ✅ API готов к использованию
[MapCore] 🚀 Начинаем инициализацию...
[MapCore] ✅ Карта готова к использованию!
[MapContainer] 🎉 Получен сигнал ready...
[YandexMap] 🎉 handleReady вызван...
[MapDiagnostics] 📊 ИТОГИ: ✅ Успешно: 5, ❌ Ошибки: 0
```

#### **Проблема с API:**
```
[MapLoader] 🚀 Начинаем загрузку...
[MapLoader] ❌ Ошибка загрузки script: Error...
[MapCore] ❌ Ошибка инициализации: Failed to load...
```

#### **Проблема с DOM:**
```
[MapCore] ❌ DOM контейнер с id="map-xyz" не найден
```

#### **Проблема с конфигурацией:**
```
[MapDiagnostics] ❌ Map Config: Неверный формат центра карты
```

## 🛠️ УТИЛИТЫ ДИАГНОСТИКИ

### **Ручная диагностика в консоли:**
```javascript
// Проверить состояние карты
__mapDiagnostics.fullDiagnostics({
  mapId: 'map-container-id',
  apiKey: '23ff8acc-835f-4e99-8b19-d33c5d346e18'
})

// Очистить результаты
__mapDiagnostics.clear()

// Получить результаты
__mapDiagnostics.getResults()
```

### **Проверка конкретных элементов:**
```javascript
// Проверить API
__mapDiagnostics.checkApiAvailability('api-key')

// Проверить DOM
__mapDiagnostics.checkDomElements('map-container-id')

// Проверить скрипты
__mapDiagnostics.checkLoadedScripts()
```

## 🚨 ЧАСТЫЕ ПРОБЛЕМЫ И РЕШЕНИЯ

### **1. Карта показывает "Загрузка карты..." бесконечно**

**Диагностика:**
- Логи обрываются на этапе `[MapLoader]` → проблема с API
- Логи доходят до `[MapCore]` но нет `ready` → проблема с созданием карты
- Есть логи `[MapCore] ✅ готова`, но нет `[YandexMap] handleReady` → проблема связи компонентов

**Решения:**
```javascript
// Проверить в консоли
console.log('window.ymaps доступен:', !!window.ymaps)
console.log('Контейнер карты:', document.getElementById('map-container-id'))
console.log('Количество скриптов API:', document.querySelectorAll('script[src*="yandex.ru"]').length)
```

### **2. Ошибка "DOM контейнер не найден"**

**Причина:** MapCore пытается создать карту до рендеринга DOM  
**Решение:** Увеличить задержку в MapCore или проверить v-if условия

### **3. Ошибка "Failed to load Yandex Maps API"**

**Причина:** Блокировка загрузки API (блокировщики рекламы, CORS, неверный URL)  
**Решение:** 
- Отключить AdBlock
- Проверить сеть в DevTools → Network
- Попробовать без API ключа

### **4. Дублирование скриптов API**

**Диагностика:** `[MapDiagnostics] ⚠️ Найдено N скриптов Yandex Maps`  
**Причина:** Повторные монтирования компонента  
**Решение:** Добавить проверку существующих скриптов

## 📋 ЧЕК-ЛИСТ ДЛЯ ОТЛАДКИ

1. ⬜ Открыть консоль браузера
2. ⬜ Очистить консоль (`Ctrl+L`)
3. ⬜ Перезагрузить страницу (`Ctrl+F5`)
4. ⬜ Проверить логи `[MapLoader]` - загрузка API
5. ⬜ Проверить логи `[MapCore]` - создание карты
6. ⬜ Проверить логи `[MapContainer]` - плагины
7. ⬜ Проверить логи `[YandexMap]` - готовность
8. ⬜ Проверить итоги диагностики `[MapDiagnostics]`
9. ⬜ Найти первую ошибку в цепочке
10. ⬜ Применить решение из руководства

## 📞 ПОЛУЧЕНИЕ ПОМОЩИ

**Если логирование не помогло:**
1. Скопируйте ВСЕ логи из консоли
2. Опишите: на какой странице, какой браузер, что ожидали
3. Приложите скриншот страницы и консоли
4. Запустите `__mapDiagnostics.getResults()` и приложите результат

**Логирование успешно добавлено! Теперь вы сможете точно определить где именно ломается загрузка карты. 🚀**