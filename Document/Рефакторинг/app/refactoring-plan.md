# 🚀 ПЛАН РЕФАКТОРИНГА ПЛАТФОРМЫ SPA
## Трансформация в модульную архитектуру (как Avito/Ozon)

### 📅 ОБЩАЯ TIMELINE: 3-4 недели
- **Неделя 1**: Подготовка и базовая инфраструктура
- **Неделя 2**: Рефакторинг core модулей
- **Неделя 3**: Рефакторинг feature модулей  
- **Неделя 4**: Тестирование и оптимизация

---

## 📊 ЭТАП 0: ПОДГОТОВКА И АУДИТ (2 дня)

### День 1: Аудит и документация
```
✅ Задачи:
1. Запустить команду: php artisan ai:context --full
2. Создать backup всего проекта
3. Настроить Git Flow (develop, feature/*, hotfix/*)
4. Документировать текущую архитектуру
5. Составить список всех зависимостей
```

### День 2: Настройка инструментов
```
✅ Задачи:
1. Установить анализаторы кода:
   - composer require --dev phpstan/phpstan
   - composer require --dev squizlabs/php_codesniffer
   
2. Настроить pre-commit hooks
3. Создать тестовое окружение
4. Настроить CI/CD pipeline (GitHub Actions)
```

---

## 🏗️ ЭТАП 1: БАЗОВАЯ ИНФРАСТРУКТУРА (3-4 дня)

### 1.1 Создание модульной структуры
```
app/
├── Domain/                    # Бизнес-логика
│   ├── User/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── DTOs/
│   │   └── Actions/
│   ├── Ad/
│   ├── Booking/
│   ├── Payment/
│   └── Master/
│
├── Application/              # Слой приложения
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Console/
│   └── Jobs/
│
├── Infrastructure/          # Инфраструктура
│   ├── Cache/
│   ├── Storage/
│   ├── External/           # Внешние API
│   └── Database/
│
└── Support/                # Вспомогательные
    ├── Traits/
    ├── Helpers/
    ├── Enums/
    └── Exceptions/
```

### 1.2 Базовые интерфейсы и контракты
```php
// app/Domain/Shared/Contracts/Repository.php
interface Repository {
    public function find(int $id): ?Model;
    public function findMany(array $criteria): Collection;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

// app/Domain/Shared/Contracts/Service.php
interface Service {
    public function execute(DTO $data): Result;
}
```

### 1.3 Создание базовых классов
```php
// app/Domain/Shared/BaseRepository.php
abstract class BaseRepository implements Repository {
    protected Model $model;
    
    public function find(int $id): ?Model {
        return $this->model->find($id);
    }
    // ... остальные методы
}

// app/Domain/Shared/BaseService.php
abstract class BaseService implements Service {
    protected array $validators = [];
    
    protected function validate(array $data): void {
        // Валидация через validators
    }
}
```

---

## 🔧 ЭТАП 2: РЕФАКТОРИНГ CORE МОДУЛЕЙ (5-6 дней)

### 2.1 Модуль User (2 дня)
```
День 1: Разделение модели User
✅ Задачи:
1. Создать app/Domain/User/Models/User.php (только аутентификация)
2. Вынести профиль в app/Domain/User/Models/UserProfile.php
3. Создать app/Domain/User/Models/UserSettings.php
4. Создать app/Domain/User/Enums/UserRole.php
5. Создать app/Domain/User/Enums/UserStatus.php

День 2: Сервисы и репозитории
✅ Задачи:
1. UserRepository (find, findByEmail, etc.)
2. UserService (register, updateProfile, changePassword)
3. UserAuthService (login, logout, verify)
4. UserDTO, UpdateProfileDTO
5. Рефакторинг контроллеров
```

#### Пример рефакторинга:
```php
// БЫЛО:
class User extends Authenticatable {
    public function isMaster() {
        return $this->role === 'master';
    }
    
    public function bookings() {
        return $this->hasMany(Booking::class, 'client_id');
    }
    // ... еще 500 строк кода
}

// СТАЛО:
// app/Domain/User/Models/User.php
class User extends Authenticatable {
    use HasRoles, HasProfile;
    
    protected $fillable = ['email', 'password'];
    
    public function profile(): HasOne {
        return $this->hasOne(UserProfile::class);
    }
}

// app/Domain/User/Models/UserProfile.php
class UserProfile extends Model {
    protected $fillable = ['name', 'phone', 'avatar'];
}

// app/Domain/User/Traits/HasRoles.php
trait HasRoles {
    public function hasRole(UserRole $role): bool {
        return $this->role === $role->value;
    }
}

// app/Domain/User/Services/UserService.php
class UserService {
    public function __construct(
        private UserRepository $users,
        private ProfileService $profiles
    ) {}
    
    public function updateProfile(int $userId, UpdateProfileDTO $data): UserProfile {
        $user = $this->users->findOrFail($userId);
        return $this->profiles->update($user->profile, $data);
    }
}
```

### 2.2 Модуль Ad (объявления) - 2 дня

```
День 1: Модели и структура
✅ Задачи:
1. Разделить Ad на несколько моделей:
   - Ad (основная информация)
   - AdContent (тексты, описания)
   - AdPricing (цены, скидки)
   - AdSchedule (расписание)
   - AdMedia (фото, видео)
   
2. Создать Enums:
   - AdStatus (draft, active, archived, blocked)
   - AdType (master, salon)
   - PaymentMethod (cash, card, transfer)

День 2: Сервисы
✅ Задачи:
1. AdRepository с методами поиска
2. AdService (create, update, publish)
3. AdMediaService (upload, process, optimize)
4. AdModerationService (check, approve, reject)
5. AdSearchService (search, filter, sort)
```

#### Пример модульной структуры:
```php
// app/Domain/Ad/Services/AdService.php
class AdService {
    public function __construct(
        private AdRepository $ads,
        private AdMediaService $media,
        private AdPricingService $pricing,
        private AdModerationService $moderation,
        private EventDispatcher $events
    ) {}
    
    public function create(CreateAdDTO $data): Ad {
        DB::transaction(function() use ($data) {
            // 1. Создаем объявление
            $ad = $this->ads->create($data->toArray());
            
            // 2. Добавляем медиа
            if ($data->hasMedia()) {
                $this->media->attach($ad, $data->media);
            }
            
            // 3. Устанавливаем цены
            $this->pricing->setPrices($ad, $data->pricing);
            
            // 4. Отправляем на модерацию
            $this->moderation->submit($ad);
            
            // 5. Событие
            $this->events->dispatch(new AdCreated($ad));
            
            return $ad;
        });
    }
}

// app/Domain/Ad/Actions/PublishAdAction.php
class PublishAdAction {
    public function execute(Ad $ad): Result {
        if (!$ad->canBePublished()) {
            return Result::error('Ad cannot be published');
        }
        
        $ad->publish();
        
        event(new AdPublished($ad));
        
        return Result::success($ad);
    }
}
```

### 2.3 Модуль Media (1 день)
```
✅ Задачи:
1. Создать универсальный MediaService
2. Унифицировать обработку фото/видео
3. Создать MediaRepository
4. Оптимизация изображений
5. CDN интеграция
```

```php
// app/Domain/Media/Services/MediaService.php
class MediaService {
    private array $processors = [
        'image' => ImageProcessor::class,
        'video' => VideoProcessor::class,
    ];
    
    public function process(UploadedFile $file, MediaType $type): Media {
        $processor = $this->getProcessor($type);
        
        return $processor
            ->validate($file)
            ->optimize()
            ->generateThumbnails()
            ->uploadToCDN()
            ->save();
    }
}
```

---

## 🎨 ЭТАП 3: РЕФАКТОРИНГ FEATURE МОДУЛЕЙ (4-5 дней)

### 3.1 Модуль Booking (1-2 дня)
```
✅ Структура:
app/Domain/Booking/
├── Models/
│   ├── Booking.php
│   ├── BookingSlot.php
│   └── BookingStatus.php
├── Services/
│   ├── BookingService.php
│   ├── SlotService.php
│   └── NotificationService.php
├── Actions/
│   ├── CreateBookingAction.php
│   ├── CancelBookingAction.php
│   └── ConfirmBookingAction.php
└── Events/
    ├── BookingCreated.php
    └── BookingCancelled.php
```

### 3.2 Модуль Search (1 день)
```php
// app/Domain/Search/Services/SearchService.php
class SearchService {
    private array $engines = [
        'ads' => AdSearchEngine::class,
        'masters' => MasterSearchEngine::class,
    ];
    
    public function search(SearchRequest $request): SearchResult {
        $engine = $this->getEngine($request->type);
        
        return $engine
            ->query($request->query)
            ->filters($request->filters)
            ->sort($request->sort)
            ->paginate($request->perPage)
            ->execute();
    }
}
```

### 3.3 Модуль Payment (1-2 дня)
```
✅ Структура:
app/Domain/Payment/
├── Gateways/
│   ├── StripeGateway.php
│   ├── YooKassaGateway.php
│   └── WebMoneyGateway.php
├── Services/
│   ├── PaymentService.php
│   └── SubscriptionService.php
└── Models/
    ├── Payment.php
    ├── Transaction.php
    └── Subscription.php
```

---

## 🧪 ЭТАП 4: ТЕСТИРОВАНИЕ И ОПТИМИЗАЦИЯ (3-4 дня)

### 4.1 Unit тесты для каждого модуля
```bash
tests/
├── Unit/
│   ├── Domain/
│   │   ├── User/
│   │   │   ├── UserServiceTest.php
│   │   │   └── UserRepositoryTest.php
│   │   ├── Ad/
│   │   └── Booking/
│   └── Application/
└── Feature/
    ├── CreateAdTest.php
    ├── BookingFlowTest.php
    └── PaymentProcessTest.php
```

### 4.2 Оптимизация производительности
```
✅ Задачи:
1. Настроить Redis кеширование
2. Оптимизировать запросы (N+1)
3. Настроить очереди для тяжелых операций
4. Добавить индексы в БД
5. Настроить CDN для медиа
```

---

## 🔄 ЭТАП 5: МИГРАЦИЯ И РАЗВЕРТЫВАНИЕ (2-3 дня)

### 5.1 Поэтапная миграция
```
1. Развернуть новую структуру параллельно
2. Переключать модули постепенно
3. Мониторинг ошибок
4. Откат при необходимости
```

### 5.2 CI/CD настройка
```yaml
# .github/workflows/deploy.yml
name: Deploy
on:
  push:
    branches: [main]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: |
          php artisan test
          npm run test
  
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        run: |
          php artisan down
          git pull origin main
          composer install --no-dev
          php artisan migrate
          php artisan cache:clear
          php artisan up
```

---

## 📈 МЕТРИКИ УСПЕХА

### До рефакторинга:
- Время на добавление фичи: 2-3 дня
- Количество багов на релиз: 10-15
- Покрытие тестами: 0%
- Время отклика API: 500-800ms

### После рефакторинга:
- Время на добавление фичи: 0.5-1 день
- Количество багов на релиз: 1-3
- Покрытие тестами: 80%+
- Время отклика API: 50-150ms

---

## 🎯 КЛЮЧЕВЫЕ ПРИНЦИПЫ

1. **Single Responsibility** - каждый класс делает одно дело
2. **Dependency Injection** - все зависимости через конструктор
3. **Interface Segregation** - маленькие специализированные интерфейсы
4. **Domain Driven Design** - бизнес-логика в Domain слое
5. **CQRS где нужно** - разделение чтения и записи

---

## 🚀 QUICK WINS (можно сделать сразу)

### Неделя 1:
1. Удалить все debug/console.log (15 минут)
2. Создать JsonFieldsTrait для моделей (1 час)
3. Вынести обработку фото в PhotoService (2 часа)
4. Создать AdService для базовых операций (3 часа)

### Результат первой недели:
- Код станет чище на 30%
- Исчезнет дублирование
- Появится базовая модульность

---

## 📝 ЧЕКЛИСТ ДЛЯ КАЖДОГО МОДУЛЯ

- [ ] Создана структура папок
- [ ] Определены модели и их связи
- [ ] Созданы репозитории
- [ ] Реализованы сервисы
- [ ] Добавлены DTO
- [ ] Написаны тесты
- [ ] Добавлена документация
- [ ] Настроено кеширование
- [ ] Оптимизированы запросы
- [ ] Проведен code review