# 📸 Управление фотографиями мастеров

## 🏗️ Структура хранения

```
public/
├── images/
│   └── masters/              # Локальные фотографии (доступны по URL)
│       ├── elena1.jpg
│       ├── elena2.jpg
│       └── ...
└── storage/                  # Симлинк на storage/app/public

storage/
└── app/
    └── public/
        └── masters/          # Загруженные фотографии
            ├── abc123.jpg
            ├── def456.jpg
            └── ...
```

## 🚀 Способы добавления фотографий

### 1. **Локальные фотографии** (уже существующие файлы)

1. **Создать тестовые изображения:**
   ```bash
   php create_test_images.php
   ```

2. **Добавить через форму:**
   - Откройте `upload_photos.html` в браузере
   - Используйте форму "Добавление локальной фотографии"
   - Укажите путь: `images/masters/elena1.jpg`

3. **Добавить через API:**
   ```javascript
   fetch('/master/photos/local', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({
           master_id: 3,
           file_path: 'images/masters/elena1.jpg',
           is_main: false
       })
   })
   ```

### 2. **Загрузка файлов** (новые файлы)

1. **Через форму:**
   - Откройте `upload_photos.html` в браузере
   - Используйте форму "Загрузка файлов"
   - Выберите файлы с компьютера

2. **Через API:**
   ```javascript
   const formData = new FormData();
   formData.append('photos[]', fileInput.files[0]);
   
   fetch('/masters/3/photos', {
       method: 'POST',
       body: formData
   })
   ```

## 🔧 API Endpoints

### Загрузка фотографий
```
POST /masters/{master}/photos
Content-Type: multipart/form-data
Body: photos[] (files)
```

### Добавление локальной фотографии
```
POST /master/photos/local
Content-Type: application/json
Body: {
    "master_id": 3,
    "file_path": "images/masters/photo.jpg",
    "is_main": false
}
```

### Удаление фотографии
```
DELETE /masters/{master}/photos/{photo}
```

### Установка главной фотографии
```
PATCH /masters/{master}/photos/{photo}/main
```

### Изменение порядка фотографий
```
PATCH /masters/{master}/photos/reorder
Body: {
    "photos": [
        {"id": 1, "order": 1},
        {"id": 2, "order": 2}
    ]
}
```

## 🗃️ База данных

### Таблица `master_photos`
```sql
CREATE TABLE master_photos (
    id BIGINT PRIMARY KEY,
    master_profile_id BIGINT,
    path VARCHAR(255),          -- Путь к файлу
    is_main BOOLEAN DEFAULT 0,  -- Главная фотография
    order INT DEFAULT 1,        -- Порядок отображения
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Модель `MasterPhoto`
```php
class MasterPhoto extends Model {
    protected $fillable = [
        'master_profile_id', 'path', 'is_main', 'order'
    ];
    
    public function getUrlAttribute() {
        return Storage::url($this->path);
    }
}
```

## 📱 Использование в компонентах

### В контроллере
```php
$master = MasterProfile::with('photos')->find(3);
$gallery = $master->photos->map(fn($photo) => [
    'id' => $photo->id,
    'url' => $photo->url,
    'is_main' => $photo->is_main
]);
```

### В Vue компоненте
```vue
<OzonStyleGallery
    :images="gallery.map(img => img.url)"
    :master-name="master.name"
    :is-premium="master.is_premium"
    :is-verified="master.is_verified"
    :rating="master.rating"
/>
```

## 🔍 Отладка

### Проверить существование файла
```php
if (file_exists(public_path('images/masters/elena1.jpg'))) {
    echo "Файл найден";
}
```

### Проверить URL
```php
echo asset('images/masters/elena1.jpg');
// http://127.0.0.1:8000/images/masters/elena1.jpg
```

### Проверить данные в базе
```php
$photos = MasterPhoto::where('master_profile_id', 3)->get();
foreach ($photos as $photo) {
    echo $photo->path . ' -> ' . $photo->url . "\n";
}
```

## 🎯 Рекомендации

1. **Размеры изображений:** 400x600px (соотношение 2:3)
2. **Форматы:** JPG, PNG, GIF
3. **Размер файла:** до 2MB
4. **Оптимизация:** используйте сжатие изображений
5. **Водяные знаки:** добавляйте для премиум-мастеров

## 🚨 Безопасность

- Валидация типов файлов
- Ограничение размера файлов
- Проверка на вредоносный код
- Права доступа к папкам (755)

## 📋 Checklist

- [ ] Создать папки для хранения
- [ ] Настроить права доступа
- [ ] Создать тестовые изображения
- [ ] Добавить фотографии через форму
- [ ] Проверить отображение в галерее
- [ ] Настроить оптимизацию изображений 