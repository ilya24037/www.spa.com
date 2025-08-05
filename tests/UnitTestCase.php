<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;

/**
 * Базовый класс для чистых Unit тестов БЕЗ Laravel framework
 * 
 * Используйте этот класс для тестов, которые НЕ требуют:
 * - База данных
 * - Laravel контейнер
 * - Service Providers
 * - События
 * - Очереди
 */
abstract class UnitTestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}