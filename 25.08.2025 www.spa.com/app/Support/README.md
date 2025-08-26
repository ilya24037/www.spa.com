# Базовые классы и интерфейсы

Эта папка содержит базовые компоненты для унификации архитектуры проекта.

## Структура

```
app/Support/
├── Contracts/
│   ├── RepositoryInterface.php  # Интерфейс для всех репозиториев
│   └── ServiceInterface.php     # Интерфейс для всех сервисов
├── Repositories/
│   └── BaseRepository.php       # Базовый класс репозитория
├── Services/
│   └── BaseService.php          # Базовый класс сервиса
├── Helpers/                     # Вспомогательные функции
└── Traits/                      # Переиспользуемые трейты
```

## Использование

### Создание нового репозитория

```php
namespace App\Domain\YourDomain\Repositories;

use App\Support\Repositories\BaseRepository;
use App\Domain\YourDomain\Models\YourModel;

class YourRepository extends BaseRepository
{
    public function __construct(YourModel $model)
    {
        parent::__construct($model);
    }
    
    // Дополнительные методы специфичные для домена
}
```

### Создание нового сервиса

```php
namespace App\Domain\YourDomain\Services;

use App\Support\Services\BaseService;
use App\Domain\YourDomain\Repositories\YourRepository;

class YourService extends BaseService
{
    public function __construct(YourRepository $repository)
    {
        parent::__construct($repository);
    }
    
    // Дополнительные методы специфичные для домена
}
```

## Доступные методы

### BaseRepository

- `find(int $id)` - найти запись по ID
- `findOrFail(int $id)` - найти или выбросить исключение
- `all()` - получить все записи
- `create(array $data)` - создать запись
- `update(int $id, array $data)` - обновить запись
- `delete(int $id)` - удалить запись
- `paginate(int $perPage = 15)` - пагинация
- `findBy(string $field, $value)` - найти по полю
- `findOneBy(string $field, $value)` - найти одну запись по полю
- `exists(int $id)` - проверить существование
- `count()` - количество записей
- `where(string $field, $operator, $value = null)` - условная выборка
- `updateOrCreate(array $attributes, array $values = [])` - обновить или создать

### BaseService

Наследует все методы от репозитория плюс:
- Автоматическое кеширование для методов `find()` и `all()`
- Транзакции для методов `create()`, `update()`, `delete()`
- Логирование всех операций
- Метод `clearCache()` для очистки кеша

## Примеры миграции

### До:
```php
class UserRepository
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }
}
```

### После:
```php
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

## Преимущества

1. **DRY принцип** - нет дублирования кода
2. **Единообразие** - все репозитории работают одинаково
3. **Кеширование** - встроено в базовый сервис
4. **Транзакции** - автоматические для критичных операций
5. **Логирование** - все операции логируются
6. **Тестируемость** - легко создавать моки через интерфейсы