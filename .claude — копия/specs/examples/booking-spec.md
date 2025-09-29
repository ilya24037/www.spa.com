# SPEC: Система бронирования услуг массажа

## 📋 Информация о задаче

- **Дата создания:** 2025-01-22
- **Автор:** AI Team
- **Статус:** Implemented
- **Связанные файлы:**
  - Backend: `app/Domain/Booking/`
  - Frontend: `resources/js/src/features/booking/`
  - API: `app/Application/Http/Controllers/Booking/`

## 🎯 Бизнес-требование

### User Story
```
Как клиент,
Я хочу забронировать услугу массажа онлайн,
Чтобы гарантировать время визита к мастеру
```

### Проблема
Клиенты не могут заранее забронировать время к мастеру, что приводит к:
- Потере клиентов (уходят к конкурентам)
- Неэффективному использованию времени мастеров
- Отсутствию предсказуемости доходов

### Решение
Внедрение системы онлайн-бронирования с календарем, выбором времени и автоматическими уведомлениями

## 📐 Функциональные требования

### Основной сценарий

1. [x] Клиент выбирает мастера из каталога
2. [x] Система показывает доступные услуги мастера
3. [x] Клиент выбирает услугу
4. [x] Система показывает календарь с доступными датами
5. [x] Клиент выбирает дату и время
6. [x] Система показывает форму подтверждения
7. [x] Клиент вводит контактные данные
8. [x] Система создает бронирование
9. [x] Клиент и мастер получают уведомления
10. [x] Результат: бронирование создано и подтверждено

### Альтернативные сценарии

#### Сценарий A: Время уже занято
1. На шаге 5, если выбранное время стало недоступным
2. Система показывает сообщение "Время уже занято"
3. Предлагает ближайшие доступные слоты
4. Результат: клиент выбирает другое время

#### Сценарий B: Отмена бронирования
1. После создания бронирования клиент хочет отменить
2. Клиент нажимает "Отменить бронирование"
3. Система запрашивает подтверждение
4. При подтверждении - отменяет и уведомляет мастера
5. Результат: бронирование отменено, слот освобожден

## 🔧 Технические требования

### API

#### Endpoint: Создание бронирования
```
Method: POST
Path: /api/v1/bookings
```

#### Request
```json
{
  "master_id": 123,
  "service_id": 45,
  "date": "2025-02-15",
  "time": "14:00",
  "duration": 90,
  "client_name": "Иван Иванов",
  "client_phone": "+7 900 123-45-67",
  "comment": "Первый визит"
}
```

#### Response (Success)
```json
{
  "status": "success",
  "data": {
    "id": 789,
    "code": "BK-2025-0789",
    "status": "confirmed",
    "master": {
      "id": 123,
      "name": "Мария Петрова"
    },
    "service": {
      "id": 45,
      "name": "Классический массаж",
      "duration": 90,
      "price": 3500
    },
    "datetime": "2025-02-15T14:00:00",
    "total_amount": 3500
  }
}
```

#### Response (Error)
```json
{
  "status": "error",
  "message": "Выбранное время недоступно",
  "errors": {
    "time": ["Этот временной слот уже занят"],
    "suggested_times": ["14:30", "15:30", "16:00"]
  }
}
```

### База данных

#### Таблица bookings
```sql
CREATE TABLE bookings (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(20) UNIQUE NOT NULL,
  master_id BIGINT NOT NULL,
  client_id BIGINT NULL,
  service_id BIGINT NOT NULL,
  status ENUM('pending', 'confirmed', 'cancelled', 'completed'),
  date DATE NOT NULL,
  time TIME NOT NULL,
  duration INT NOT NULL,
  total_amount DECIMAL(10,2),
  client_name VARCHAR(255),
  client_phone VARCHAR(20),
  comment TEXT,
  cancelled_at TIMESTAMP NULL,
  cancellation_reason TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  INDEX idx_master_date (master_id, date),
  INDEX idx_status (status),
  FOREIGN KEY (master_id) REFERENCES master_profiles(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
);
```

#### Таблица booking_slots
```sql
CREATE TABLE booking_slots (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  master_id BIGINT NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  is_available BOOLEAN DEFAULT TRUE,
  booking_id BIGINT NULL,

  UNIQUE KEY unique_slot (master_id, date, time),
  FOREIGN KEY (master_id) REFERENCES master_profiles(id),
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
```

### Frontend компоненты

#### Новые компоненты
- `BookingCalendar.vue` - Календарь с выбором даты
- `TimeSlotPicker.vue` - Выбор временного слота
- `BookingForm.vue` - Форма бронирования
- `BookingConfirmation.vue` - Подтверждение бронирования
- `useBookingFlow.ts` - Composable для управления процессом

#### Изменяемые компоненты
- `MasterCard.vue` - Добавлена кнопка "Записаться"
- `ServiceList.vue` - Добавлен выбор для бронирования

## ✅ Критерии приемки

### Функциональные критерии
- [x] Клиент может выбрать дату не ранее завтра
- [x] Показываются только доступные временные слоты
- [x] Бронирование создается в статусе "confirmed"
- [x] Уведомления отправляются мастеру и клиенту
- [x] Можно отменить бронирование за 24 часа

### Технические критерии
- [x] Время отклика API < 200ms (p95)
- [x] Нет race condition при одновременном бронировании
- [x] TypeScript строго типизирован
- [x] Тесты покрывают > 85%

### UX критерии
- [x] Загрузка календаря < 1 секунды
- [x] Понятные сообщения об ошибках
- [x] Loading состояния при загрузке слотов
- [x] Работает на мобильных устройствах
- [x] Доступность: можно использовать с клавиатуры

## 🚫 Ограничения и edge cases

### Ограничения
- Максимум 30 дней вперед для бронирования
- Минимум 2 часа до времени визита
- Не более 3 активных бронирований на клиента
- Работает с 8:00 до 22:00

### Edge cases
1. **Все слоты заняты**
   - Показываем "Нет доступного времени"
   - Предлагаем выбрать другую дату

2. **Двойное бронирование**
   - Используем транзакции БД
   - Проверка доступности перед сохранением
   - Сообщение "Время уже занято"

3. **Мастер заблокировал день**
   - День недоступен в календаре
   - Подсказка "Мастер не работает"

4. **Отмена в последний момент**
   - За 2 часа - требует подтверждения
   - Менее 2 часов - запрещено

## 🧪 Тестовые сценарии

### Unit тесты
```php
// Backend
test('should_not_allow_double_booking', function () {
    // Arrange
    $master = Master::factory()->create();
    $slot = '2025-02-15 14:00';

    // Act - первое бронирование
    $booking1 = BookingService::create($master, $slot, $data1);

    // Act - попытка второго бронирования
    $result = BookingService::create($master, $slot, $data2);

    // Assert
    expect($result)->toBeFalse();
    expect($booking1)->toBeInstanceOf(Booking::class);
});
```

```typescript
// Frontend
describe('BookingCalendar', () => {
  it('should disable past dates', () => {
    const wrapper = mount(BookingCalendar);
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    const yesterdayCell = wrapper.find(`[data-date="${yesterday}"]`);
    expect(yesterdayCell.classes()).toContain('disabled');
  });
});
```

### E2E сценарий
1. Открыть карточку мастера
2. Нажать "Записаться"
3. Выбрать услугу "Классический массаж"
4. Выбрать дату на следующей неделе
5. Выбрать время 14:00
6. Заполнить форму контактов
7. Нажать "Подтвердить"
8. Проверить появление сообщения "Вы записаны!"

## 📊 Метрики успеха

- Конверсия просмотр → бронирование: > 15%
- Количество отмен: < 10%
- Среднее время создания бронирования: < 2 минут
- Удовлетворенность клиентов (NPS): > 70

## 🔄 Миграция и обратная совместимость

### План миграции
1. Feature flag `bookings_enabled`
2. Запуск для 10% мастеров
3. Мониторинг 1 неделю
4. Расширение до 50%
5. Полный запуск через 2 недели

### Обратная совместимость
- [x] Звонки по телефону продолжают работать
- [x] Существующие записи мигрированы
- [x] API версия v1 стабильна

## 📝 Дополнительные заметки

- Интеграция с Google Calendar - в следующей версии
- SMS-напоминания - требуют отдельной спецификации
- Онлайн-оплата - отдельный проект

## 🔗 Ссылки

- Дизайн: [Figma - Booking Flow](https://figma.com/spa-booking)
- Обсуждение: Issue #234
- Метрики: [Dashboard](https://analytics.spa.com/bookings)

---

*Пример реальной спецификации для SPA Platform*