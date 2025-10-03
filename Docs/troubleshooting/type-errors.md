# Устранение Type Errors в Laravel + Inertia

## TypeError: Return value must be of type X, Y returned

### Проблема
При использовании Inertia.js с Laravel возникает ошибка несоответствия типов возврата:
```
TypeError: Return value must be of type Illuminate\Http\RedirectResponse, Illuminate\Http\Response returned
```

### Причины
1. Метод контроллера объявлен с конкретным типом возврата (например, `RedirectResponse`)
2. Но использует `Inertia::location()` или `Inertia::render()`, которые возвращают другой тип

### Решения

#### Решение 1: Использовать правильный метод редиректа
```php
// ❌ Неправильно - возвращает Response
public function update(Request $request): RedirectResponse {
    // ...
    return Inertia::location('/profile/items');
}

// ✅ Правильно - возвращает RedirectResponse
public function update(Request $request): RedirectResponse {
    // ...
    return redirect('/profile/items');
}
```

#### Решение 2: Изменить тип возврата метода
```php
// Использовать Union типы
public function update(Request $request): RedirectResponse|Response {
    // Теперь можно использовать и redirect() и Inertia::location()
    if ($condition) {
        return redirect('/path');
    }
    return Inertia::render('Page');
}
```

#### Решение 3: Использовать базовый тип
```php
use Symfony\Component\HttpFoundation\Response;

public function update(Request $request): Response {
    // Может возвращать любой тип ответа
    return Inertia::location('/profile/items');
}
```

## Таблица соответствия методов и типов

| Метод | Возвращаемый тип | Использование |
|-------|------------------|---------------|
| `redirect()` | `RedirectResponse` | Обычный HTTP редирект |
| `Inertia::render()` | `Response` | Рендеринг Inertia страницы |
| `Inertia::location()` | `Response` | Жесткий редирект с перезагрузкой |
| `back()` | `RedirectResponse` | Возврат на предыдущую страницу |
| `response()->json()` | `JsonResponse` | JSON ответ для API |

## Рекомендации

1. **Консистентность** - используйте один подход во всем проекте
2. **Простота** - для обычных редиректов используйте `redirect()`
3. **Inertia специфика** - `Inertia::location()` только когда нужна полная перезагрузка
4. **Типизация** - всегда указывайте правильные типы возврата

## Пример правильной организации контроллера

```php
class AdController extends Controller
{
    // Для форм создания/редактирования - Response
    public function create(): Response
    {
        return Inertia::render('Ad/Create');
    }

    // Для сохранения - RedirectResponse
    public function store(Request $request): RedirectResponse
    {
        // ...
        return redirect()->route('ads.show', $ad);
    }

    // Для просмотра - Response
    public function show(Ad $ad): Response
    {
        return Inertia::render('Ad/Show', ['ad' => $ad]);
    }

    // Для обновления - RedirectResponse
    public function update(Request $request, Ad $ad): RedirectResponse
    {
        // ...
        return redirect()->route('ads.show', $ad);
    }

    // Для удаления - RedirectResponse
    public function destroy(Ad $ad): RedirectResponse
    {
        // ...
        return redirect()->route('ads.index');
    }
}
```

## Отладка

### Проверка типа возврата
```php
// В методе контроллера
$response = Inertia::location('/path');
dd(get_class($response)); // Покажет реальный класс

// Или используйте PHPStan/Psalm для статического анализа
```

### Логирование для отладки
```php
public function update(Request $request, Ad $ad): RedirectResponse
{
    Log::info('Update started', ['ad_id' => $ad->id]);

    try {
        // логика обновления

        Log::info('Redirecting to', ['path' => '/profile/items']);
        return redirect('/profile/items');
    } catch (\Exception $e) {
        Log::error('Update failed', ['error' => $e->getMessage()]);
        return back()->withErrors(['error' => 'Ошибка обновления']);
    }
}
```