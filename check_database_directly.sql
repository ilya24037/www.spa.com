-- ==========================================
-- ПРОВЕРКА СОХРАНЕНИЯ ПОЛЕЙ В БД
-- ==========================================

-- 1. Проверяем последние 5 объявлений
SELECT
    id,
    title,
    status,
    is_published,
    specialty,
    work_format,
    service_provider,
    created_at
FROM ads
ORDER BY id DESC
LIMIT 5;

-- 2. Проверяем объявления где заполнены проблемные поля
SELECT
    id,
    title,
    specialty,
    work_format,
    service_provider
FROM ads
WHERE
    specialty IS NOT NULL
    OR work_format IS NOT NULL
    OR service_provider IS NOT NULL
ORDER BY id DESC
LIMIT 10;

-- 3. Статистика по заполненности полей
SELECT
    COUNT(*) as total_ads,
    COUNT(specialty) as with_specialty,
    COUNT(work_format) as with_work_format,
    COUNT(service_provider) as with_service_provider
FROM ads;

-- 4. Конкретные объявления из логов
SELECT
    id,
    title,
    status,
    is_published,
    specialty,
    work_format,
    service_provider,
    created_at
FROM ads
WHERE id IN (27, 28, 29)
ORDER BY id;