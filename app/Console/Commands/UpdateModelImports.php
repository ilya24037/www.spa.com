<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateModelImports extends Command
{
    protected $signature = 'refactor:update-model-imports';
    protected $description = 'Update all model imports from App\Models to Domain structure';

    private array $modelMappings = [
        // Core Domain Models
        'App\Models\User' => 'App\Domain\User\Models\User',
        'App\Models\MasterProfile' => 'App\Domain\Master\Models\MasterProfile',
        'App\Models\Ad' => 'App\Domain\Ad\Models\Ad',
        'App\Models\Service' => 'App\Domain\Service\Models\Service',
        'App\Models\Booking' => 'App\Domain\Booking\Models\Booking',
        'App\Models\Review' => 'App\Domain\Review\Models\Review',
        'App\Models\Payment' => 'App\Domain\Payment\Models\Payment',
        
        // Master Domain Models  
        'App\Models\MasterPhoto' => 'App\Domain\Media\Models\Photo',
        'App\Models\MasterVideo' => 'App\Domain\Media\Models\Video',
        'App\Models\MasterSubscription' => 'App\Domain\Master\Models\MasterSubscription',
        'App\Models\Schedule' => 'App\Domain\Master\Models\Schedule',
        'App\Models\ScheduleException' => 'App\Domain\Master\Models\ScheduleException',
        'App\Models\WorkZone' => 'App\Domain\Master\Models\WorkZone',
        
        // Ad Domain Models
        'App\Models\AdContent' => 'App\Domain\Ad\Models\AdContent',
        'App\Models\AdMedia' => 'App\Domain\Ad\Models\AdMedia',
        'App\Models\AdPricing' => 'App\Domain\Ad\Models\AdPricing',
        'App\Models\AdSchedule' => 'App\Domain\Ad\Models\AdSchedule',
        'App\Models\AdPlan' => 'App\Domain\Ad\Models\AdPlan',
        
        // Service Domain Models (ДОБАВЛЕНО: отсутствовало)
        'App\Models\MassageCategory' => 'App\Domain\Service\Models\MassageCategory',
        
        // Booking Domain Models
        'App\Models\BookingService' => 'App\Domain\Booking\Models\BookingService',
        'App\Models\BookingSlot' => 'App\Domain\Booking\Models\BookingSlot',
        
        // Review Domain Models
        'App\Models\ReviewReply' => 'App\Domain\Review\Models\ReviewReply',
        
        // Media Domain Models (универсальные медиа файлы)
        'App\Models\Media' => 'App\Domain\Media\Models\Media',
        'App\Models\Photo' => 'App\Domain\Media\Models\Photo',
        'App\Models\Video' => 'App\Domain\Media\Models\Video',
        
        // User Domain Models
        'App\Models\UserBalance' => 'App\Domain\User\Models\UserBalance',
        'App\Models\Subscription' => 'App\Domain\User\Models\Subscription',
        
        // Notification Domain Models
        'App\Models\Notification' => 'App\Domain\Notification\Models\Notification',
        'App\Models\NotificationDelivery' => 'App\Domain\Notification\Models\NotificationDelivery',
        
        // Project Domain Models
        'App\Models\Project' => 'App\Domain\Project\Models\Project',
    ];

    public function handle()
    {
        $this->info('Starting model import updates...');
        
        $files = $this->getPhpFiles();
        $updatedCount = 0;
        $filesUpdated = 0;
        $detailedResults = [];
        
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        
        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;
            $fileUpdated = false;
            $replacements = [];
            
            foreach ($this->modelMappings as $old => $new) {
                // Update use statements
                $usePattern = '/use\s+' . preg_quote($old, '/') . '\s*;/';
                if (preg_match($usePattern, $content)) {
                    $content = preg_replace($usePattern, "use $new;", $content);
                    $updatedCount++;
                    $fileUpdated = true;
                    $replacements[] = "use $old → use $new";
                }
                
                // Update inline class references (\\App\\Models\\Something)
                $inlinePattern = '/\\\\' . preg_quote($old, '/') . '(?=[^\w])/';
                if (preg_match($inlinePattern, $content)) {
                    $content = preg_replace($inlinePattern, '\\' . $new, $content);
                    $updatedCount++;
                    $fileUpdated = true;
                    $replacements[] = "inline $old → $new";
                }
                
                // Update string references (like 'App\Models\Booking' in Payment events)
                $stringPattern = '/[\'"]' . preg_quote($old, '/') . '[\'"]/';
                if (preg_match($stringPattern, $content)) {
                    $content = preg_replace($stringPattern, "'$new'", $content);
                    $updatedCount++;
                    $fileUpdated = true;
                    $replacements[] = "string '$old' → '$new'";
                }
                
                // Update class references in docblocks
                $docblockPattern = '/@(param|return|var|throws)\s+' . preg_quote($old, '/') . '(?=[^\w])/';
                if (preg_match($docblockPattern, $content)) {
                    $content = preg_replace($docblockPattern, '@$1 ' . $new, $content);
                    $updatedCount++;
                    $fileUpdated = true;
                    $replacements[] = "docblock $old → $new";
                }
                
                // Update ::class references
                $classPattern = '/' . preg_quote($old, '/') . '::class/';
                if (preg_match($classPattern, $content)) {
                    $content = preg_replace($classPattern, $new . '::class', $content);
                    $updatedCount++;
                    $fileUpdated = true;
                    $replacements[] = "::class $old → $new";
                }
            }
            
            if ($fileUpdated && $content !== $originalContent) {
                File::put($file, $content);
                $filesUpdated++;
                $relativeFile = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
                $detailedResults[$relativeFile] = $replacements;
                $this->line("\nUpdated: " . $relativeFile);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->newLine(2);
        $this->info("Model import update completed!");
        $this->info("Total replacements: $updatedCount");
        $this->info("Files updated: $filesUpdated");
        
        // Show detailed results
        if ($filesUpdated > 0) {
            $this->newLine();
            $this->info("📊 Detailed changes by file:");
            foreach ($detailedResults as $file => $changes) {
                $this->line("\n📁 $file:");
                foreach ($changes as $change) {
                    $this->line("  ✓ $change");
                }
            }
        } else {
            $this->comment("No model imports found to update.");
        }
        
        // Show files still using old imports
        $this->checkRemainingOldImports();
        
        return 0;
    }

    /**
     * Get all PHP files including app, database, tests, routes, config
     */
    protected function getPhpFiles(): array
    {
        $directories = [
            app_path(),
            database_path(),
            base_path('tests'),
            base_path('routes'),
            base_path('config'),
        ];
        
        $allFiles = [];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $allFiles[] = $file->getPathname();
                    }
                }
            }
        }
        
        return $allFiles;
    }

    private function checkRemainingOldImports()
    {
        $this->info("\n🔍 Checking for remaining old imports...");
        
        $files = $this->getPhpFiles();
        $filesWithOldImports = [];

        foreach ($files as $file) {
            // Skip Models directory itself
            if (str_contains($file, 'app' . DIRECTORY_SEPARATOR . 'Models')) {
                continue;
            }

            $content = File::get($file);
            
            if (str_contains($content, 'use App\Models\\') || 
                str_contains($content, 'App\\Models\\') ||
                str_contains($content, "'App\\Models\\") ||
                str_contains($content, '"App\\Models\\')) {
                $relativeFile = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
                $filesWithOldImports[] = $relativeFile;
            }
        }

        if (count($filesWithOldImports) > 0) {
            $this->warn("\n⚠️  Files still using old imports (" . count($filesWithOldImports) . "):");
            foreach ($filesWithOldImports as $file) {
                $this->line("  - $file");
            }
            $this->newLine();
            $this->comment("💡 These files may contain models not in the mapping or need manual review.");
        } else {
            $this->info("\n✅ No files using old imports found!");
        }
    }
}