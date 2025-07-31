<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiContext\AiContextService;

class AiContextGenerator extends Command
{
    protected $signature = 'ai:context 
                            {--quick : Быстрый режим - только основное}
                            {--full : Полный дамп проекта}
                            {--auto : Автоматический режим без вопросов}
                            {--format=markdown : Формат вывода (markdown/json)}';
    
    protected $description = 'Генерирует контекст проекта для ИИ помощника (полностью автоматический)';

    private AiContextService $contextService;
    private float $startTime;
    
    public function __construct(AiContextService $contextService)
    {
        parent::__construct();
        $this->contextService = $contextService;
    }
    
    public function handle()
    {
        $this->startTime = microtime(true);
        
        // Приветствие (только если не автоматический режим)
        if (!$this->option('auto')) {
            $this->showWelcome();
        }
        
        $this->info('🤖 Автоматическая генерация контекста проекта...');
        
        // Определяем режим
        $mode = $this->option('quick') ? 'quick' : ($this->option('full') ? 'full' : 'normal');
        
        // Генерируем контекст
        $result = $this->contextService->generate($mode);
        
        // Сохраняем
        $metadata = $this->contextService->save($result['content']);
        
        // Показываем результаты
        if (!$this->option('auto')) {
            $this->showResults($metadata, $mode);
            $this->showStats($result['stats']);
        } else {
            $this->info("✅ Контекст обновлен: AI_CONTEXT.md");
        }
        
        $this->info('✅ Контекст успешно сгенерирован!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Показать приветствие
     */
    private function showWelcome(): void
    {
        $this->info('');
        $this->info('🤖 ===============================================');
        $this->info('   УМНЫЙ АНАЛИЗ ПРОЕКТА ДЛЯ ИИ ПОМОЩНИКА');
        $this->info('🤖 ===============================================');
        $this->info('');
        $this->info('⏳ Анализирую проект...');
        $this->info('');
    }
    
    /**
     * Показать результаты
     */
    private function showResults(array $metadata, string $mode): void
    {
        $this->info("");
        $this->info("📄 Контекст сохранён: {$metadata['files']['archive']}");
        $this->info("📋 Архив версий: storage/ai-sessions/");
        $this->info("🎯 ГЛАВНЫЙ ФАЙЛ: AI_CONTEXT.md (в корне проекта)");
        $this->info("");
        $this->info("💡 КАК ИСПОЛЬЗОВАТЬ:");
        $this->info("   1. Откройте AI_CONTEXT.md в корне проекта");
        $this->info("   2. Скопируйте весь содержимое (Ctrl+A, Ctrl+C)");
        $this->info("   3. Вставьте в чат с ИИ помощником");
        $this->info("   4. Спросите: 'Что делать дальше?' или 'Проанализируй проект'");
        $this->info("");
    }
    
    /**
     * Показать статистику
     */
    private function showStats(array $stats): void
    {
        $executionTime = round(microtime(true) - $this->startTime, 2);
        
        $this->info("");
        $this->table(
            ['📊 Метрика', 'Значение'],
            [
                ['📝 Строк в отчёте', number_format($stats['lines'])],
                ['📦 Размер файла', $stats['size_formatted']],
                ['📚 Количество слов', number_format($stats['words'])],
                ['⏱️ Время генерации', $executionTime . ' сек'],
                ['🔧 Режим анализа', $this->option('quick') ? 'Быстрый' : ($this->option('full') ? 'Полный' : 'Обычный')],
            ]
        );
    }
}