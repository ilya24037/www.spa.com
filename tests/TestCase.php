<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Указывает, должны ли миграции запускаться перед каждым тестом
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // НЕ запускаем миграции для unit тестов
        // Миграции должны запускаться только в Feature тестах с RefreshDatabase трейтом
    }
}
