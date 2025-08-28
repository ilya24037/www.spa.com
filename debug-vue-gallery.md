# 🔍 ИНСТРУКЦИЯ ПО ОТЛАДКЕ Vue ГАЛЕРЕИ

## 📱 ЧТО НУЖНО СДЕЛАТЬ:

### 1. Откройте страницу в браузере
- URL: `http://spa.test/masters/klassiceskii-massaz-ot-anny-1`
- Нажмите **F12** для открытия Developer Tools

### 2. Проверьте Console (вкладка Console)
Должны появиться отладочные сообщения:

```
🔍 galleryImages DEBUG: {
  props.master: { id: 1, photos: [...] },
  props.master?.photos: [...],
  photos length: 4,
  first photo: { url: "/storage/photos/1/...", ... }
}
```

**Если видите:**
- ✅ `photos length: 4` → Фото данные есть
- ❌ `photos length: 0` или `undefined` → Проблема с данными
- ❌ `props.master: null` → Props не передаются

### 3. Если данные есть, проверьте что происходит дальше:

**Должно быть:**
```
✅ Используем photos из props.master: [объекты с url]
```

**Если видите:**
```
❌ Используем default изображения - фото не найдены!
```
→ Данные есть, но не проходят условие `if (props.master?.photos && props.master.photos.length > 0)`

### 4. Проверьте Network (вкладка Network)
- Обновите страницу (Ctrl+F5)
- Найдите запрос: `masters/klassiceskii-massaz-ot-anny-1`
- Кликните на запрос → Response
- Найдите `props.master.photos` - должно быть 4 элемента

### 5. Проверьте структуру фотографий
В Console выполните:
```javascript
// Проверяем структуру фото
console.log('Photo structure:', window.page?.props?.master?.photos?.[0])
```

## 🎯 ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ:

### Если все работает правильно:
1. Console: `photos length: 4`
2. Console: `✅ Используем photos из props.master`
3. На странице: отображается первое фото + "📷 4 фото"
4. При клике: открывается галерея

### Если есть проблема:
1. Console: `❌ Используем default изображения`
2. На странице: "Нет фотографий"
3. Галерея не работает

## 📋 ЧТО ПРОВЕРИТЬ В КОДЕ:

### Формат фотографий должен быть:
```javascript
props.master.photos = [
  { url: "/storage/photos/1/photo1.webp", thumbnail_url: "...", alt: "..." },
  { url: "/storage/photos/1/photo2.webp", thumbnail_url: "...", alt: "..." },
  // ...
]
```

### Если формат другой (например простые строки):
```javascript
props.master.photos = [
  "/storage/photos/1/photo1.webp",
  "/storage/photos/1/photo2.webp"
]
```

То код `photo.url` вернет `undefined`, и галерея не работает.

---

**📱 СКОПИРУЙТЕ И ПРИШЛИТЕ РЕЗУЛЬТАТЫ:**
- Все сообщения из Console
- Любые ошибки
- Скриншот если нужно

Это поможет найти точную причину проблемы!