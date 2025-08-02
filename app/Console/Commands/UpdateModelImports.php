<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateModelImports extends Command
{
    protected $signature = 'refactor:update-model-imports';
    protected $description = 'Update all model imports from App\Models to Domain structure';

    private array $modelMappings = [
        'App\Models\User' => 'App\Domain\User\Models\User',
        'App\Models\Ad' => 'App\Domain\Ad\Models\Ad',
        'App\Models\AdContent' => 'App\Domain\Ad\Models\AdContent',
        'App\Models\AdMedia' => 'App\Domain\Ad\Models\AdMedia',
        'App\Models\AdPricing' => 'App\Domain\Ad\Models\AdPricing',
        'App\Models\AdSchedule' => 'App\Domain\Ad\Models\AdSchedule',
        'App\Models\Service' => 'App\Domain\Service\Models\Service',
        'App\Models\Booking' => 'App\Domain\Booking\Models\Booking',
        'App\Models\BookingService' => 'App\Domain\Booking\Models\BookingService',
        'App\Models\BookingSlot' => 'App\Domain\Booking\Models\BookingSlot',
        'App\Models\Review' => 'App\Domain\Review\Models\Review',
        'App\Models\ReviewReply' => 'App\Domain\Review\Models\ReviewReply',
        'App\Models\MasterProfile' => 'App\Domain\Master\Models\MasterProfile',
        'App\Models\MasterPhoto' => 'App\Domain\Media\Models\Photo',
        'App\Models\MasterVideo' => 'App\Domain\Media\Models\Video',
        'App\Models\Payment' => 'App\Domain\Payment\Models\Payment',
        'App\Models\Media' => 'App\Domain\Media\Models\Media',
        'App\Models\Notification' => 'App\Domain\Notification\Models\Notification',
        'App\Models\NotificationDelivery' => 'App\Domain\Notification\Models\NotificationDelivery',
        'App\Models\UserBalance' => 'App\Domain\User\Models\UserBalance',
        'App\Models\Schedule' => 'App\Domain\Master\Models\Schedule',
        'App\Models\Project' => 'App\Domain\Project\Models\Project',
    ];

    public function handle()
    {
        $this->info('Starting model import updates...');

        $files = File::allFiles(app_path());
        $updatedCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            
            // Skip Models directory itself
            if (str_contains($path, 'app\Models')) {
                continue;
            }

            $content = File::get($path);
            $originalContent = $content;

            foreach ($this->modelMappings as $oldImport => $newImport) {
                // Update use statements
                $content = str_replace(
                    "use {$oldImport};",
                    "use {$newImport};",
                    $content
                );

                // Update in code (with backslashes)
                $oldClass = str_replace('\\', '\\\\', $oldImport);
                $newClass = str_replace('\\', '\\\\', $newImport);
                $content = str_replace($oldClass, $newClass, $content);
            }

            if ($content !== $originalContent) {
                File::put($path, $content);
                $updatedCount++;
                $this->line("Updated: {$path}");
            }
        }

        $this->info("✅ Updated {$updatedCount} files");
        
        // Show files still using old imports
        $this->checkRemainingOldImports();
    }

    private function checkRemainingOldImports()
    {
        $this->info("\nChecking for remaining old imports...");
        
        $files = File::allFiles(app_path());
        $filesWithOldImports = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, 'app\Models')) {
                continue;
            }

            $content = File::get($path);
            
            if (str_contains($content, 'use App\Models\\') || 
                str_contains($content, 'App\\Models\\')) {
                $filesWithOldImports[] = $path;
            }
        }

        if (count($filesWithOldImports) > 0) {
            $this->warn("\n⚠️  Files still using old imports:");
            foreach ($filesWithOldImports as $file) {
                $this->line("  - {$file}");
            }
        } else {
            $this->info("\n✅ No files using old imports found!");
        }
    }
}