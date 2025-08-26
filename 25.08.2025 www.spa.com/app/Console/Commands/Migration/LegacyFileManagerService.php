<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;

/**
 * Сервис управления legacy файлами
 */
class LegacyFileManagerService
{
    /**
     * Архивировать legacy файлы
     */
    public function archiveLegacyFiles(Command $command): void
    {
        $archiveDir = storage_path('archive/legacy_' . date('Y-m-d'));
        
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        $legacyFiles = $this->getLegacyFiles();

        foreach ($legacyFiles as $file) {
            if (file_exists($file)) {
                $destination = $archiveDir . '/' . basename($file);
                rename($file, $destination);
                $command->line("Архивирован: " . basename($file));
            }
        }
    }

    /**
     * Обновить импорты в контроллерах
     */
    public function updateControllerImports(string $service, Command $command): void
    {
        $command->line("Обновляем импорты для {$service}...");
        
        $controllers = $this->findControllers();
        
        foreach ($controllers as $controller) {
            $this->updateImportsInFile($controller, $service, $command);
        }
    }

    /**
     * Обновить сервис провайдеры
     */
    public function updateServiceProviders(): void
    {
        $configPath = config_path('app.php');
        $config = file_get_contents($configPath);
        
        if (!str_contains($config, 'AdapterServiceProvider')) {
            $config = str_replace(
                "'providers' => ServiceProvider::defaultProviders()->merge([",
                "'providers' => ServiceProvider::defaultProviders()->merge([\n        App\Providers\AdapterServiceProvider::class,",
                $config
            );
            
            file_put_contents($configPath, $config);
        }
    }

    /**
     * Получить список legacy файлов
     */
    private function getLegacyFiles(): array
    {
        return [
            app_path('Services/BookingService.php.old'),
            app_path('Services/SearchService.php.old'),
            app_path('Services/MasterService.php.old'),
            app_path('Services/PaymentService.php.old'),
        ];
    }

    /**
     * Найти все контроллеры
     */
    private function findControllers(): array
    {
        $controllerPath = app_path('Application/Http/Controllers');
        return glob($controllerPath . '/*.php');
    }

    /**
     * Обновить импорты в файле
     */
    private function updateImportsInFile(string $filePath, string $service, Command $command): void
    {
        $content = file_get_contents($filePath);
        
        // Здесь должна быть логика замены импортов
        $oldImport = "use App\\Services\\{$service};";
        $newImport = "use App\\Domain\\Services\\{$service};";
        
        if (str_contains($content, $oldImport)) {
            $content = str_replace($oldImport, $newImport, $content);
            file_put_contents($filePath, $content);
            $command->line("Обновлены импорты в: " . basename($filePath));
        }
    }
}