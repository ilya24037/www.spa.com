<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateServiceImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refactor:update-service-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update service imports from old App\Services namespace to new Domain/Infrastructure structure';

    /**
     * Mapping of old imports to new ones
     *
     * @var array
     */
    protected $replacements = [
        // Services
        'App\Services\AdMediaService' => 'App\Domain\Ad\Services\AdMediaService',
        'App\Services\AdModerationService' => 'App\Domain\Ad\Services\AdModerationService',
        'App\Services\AdSearchService' => 'App\Domain\Ad\Services\AdSearchService',
        'App\Services\AdService' => 'App\Domain\Ad\Services\AdService',
        'App\Services\MasterService' => 'App\Domain\Master\Services\MasterService',
        'App\Services\PaymentGatewayService' => 'App\Domain\Payment\Services\PaymentGatewayService',
        'App\Services\PaymentService' => 'App\Domain\Payment\Services\PaymentService',
        'App\Services\ReviewService' => 'App\Domain\Review\Services\ReviewService',
        'App\Services\SearchService' => 'App\Domain\Search\Services\SearchService',
        'App\Services\UserAuthService' => 'App\Domain\User\Services\UserAuthService',
        'App\Services\UserService' => 'App\Domain\User\Services\UserService',
        'App\Services\CacheService' => 'App\Infrastructure\Cache\CacheService',
        'App\Services\BookingService' => 'App\Domain\Booking\Services\BookingService',
        'App\Services\MediaService' => 'App\Domain\Media\Services\MediaService',
        'App\Services\AIMediaService' => 'App\Infrastructure\Media\AIMediaService',
        'App\Services\NotificationService' => 'App\Infrastructure\Notification\NotificationService',
        'App\Services\LegacyNotificationService' => 'App\Infrastructure\Notification\LegacyNotificationService',
        'App\Services\FeatureFlagService' => 'App\Infrastructure\Feature\FeatureFlagService',
        
        // DTOs
        'App\DTOs\MasterFilterDTO' => 'App\Domain\Master\DTOs\MasterFilterDTO',
        'App\DTOs\CreateBookingDTO' => 'App\Domain\Booking\DTOs\CreateBookingDTO',
        'App\DTOs\BookingFilterDTO' => 'App\Domain\Booking\DTOs\BookingFilterDTO',
        'App\DTOs\Review\CreateReviewDTO' => 'App\Domain\Review\DTOs\CreateReviewDTO',
        'App\DTOs\Review\UpdateReviewDTO' => 'App\Domain\Review\DTOs\UpdateReviewDTO',
        'App\DTOs\CreateUserDTO' => 'App\Domain\User\DTOs\CreateUserDTO',
        'App\DTOs\UpdateUserDTO' => 'App\Domain\User\DTOs\UpdateUserDTO',
        'App\DTOs\UserRegistrationDTO' => 'App\Domain\User\DTOs\UserRegistrationDTO',
        'App\DTOs\CreateMasterDTO' => 'App\Domain\Master\DTOs\CreateMasterDTO',
        'App\DTOs\UpdateMasterDTO' => 'App\Domain\Master\DTOs\UpdateMasterDTO',
        'App\DTOs\CreatePaymentDTO' => 'App\Domain\Payment\DTOs\CreatePaymentDTO',
        'App\DTOs\ProcessPaymentDTO' => 'App\Domain\Payment\DTOs\ProcessPaymentDTO',
        'App\DTOs\SendNotificationDTO' => 'App\Domain\Notification\DTOs\SendNotificationDTO',
        'App\DTOs\CreateAdDTO' => 'App\Domain\Ad\DTOs\CreateAdDTO',
        'App\DTOs\UpdateAdDTO' => 'App\Domain\Ad\DTOs\UpdateAdDTO',
        
        // Repositories
        'App\Repositories\MediaRepository' => 'App\Domain\Media\Repositories\MediaRepository',
        'App\Repositories\UserRepository' => 'App\Domain\User\Repositories\UserRepository',
        'App\Repositories\SearchRepository' => 'App\Domain\Search\Repositories\SearchRepository',
        'App\Repositories\MasterRepository' => 'App\Domain\Master\Repositories\MasterRepository',
        'App\Repositories\ReviewRepository' => 'App\Domain\Review\Repositories\ReviewRepository',
        'App\Repositories\PaymentRepository' => 'App\Domain\Payment\Repositories\PaymentRepository',
        'App\Repositories\BookingRepository' => 'App\Domain\Booking\Repositories\BookingRepository',
        'App\Repositories\AdRepository' => 'App\Domain\Ad\Repositories\AdRepository',
        'App\Repositories\NotificationRepository' => 'App\Domain\Notification\Repositories\NotificationRepository',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting service import updates...');
        
        $files = $this->getPhpFiles();
        $updatedCount = 0;
        $filesUpdated = 0;
        
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        
        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;
            $fileUpdated = false;
            
            foreach ($this->replacements as $old => $new) {
                // Update use statements
                $usePattern = '/use\s+' . preg_quote($old, '/') . '\s*;/';
                if (preg_match($usePattern, $content)) {
                    $content = preg_replace($usePattern, "use $new;", $content);
                    $updatedCount++;
                    $fileUpdated = true;
                }
                
                // Update inline class references
                $inlinePattern = '/\\\\' . preg_quote($old, '/') . '(?=[^\w])/';
                if (preg_match($inlinePattern, $content)) {
                    $content = preg_replace($inlinePattern, '\\' . $new, $content);
                    $updatedCount++;
                    $fileUpdated = true;
                }
                
                // Update class references in docblocks
                $docblockPattern = '/@(param|return|var|throws)\s+' . preg_quote($old, '/') . '(?=[^\w])/';
                if (preg_match($docblockPattern, $content)) {
                    $content = preg_replace($docblockPattern, '@$1 ' . $new, $content);
                    $updatedCount++;
                    $fileUpdated = true;
                }
            }
            
            if ($fileUpdated && $content !== $originalContent) {
                File::put($file, $content);
                $filesUpdated++;
                $this->line("\nUpdated: " . str_replace(base_path() . '/', '', $file));
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->newLine(2);
        $this->info("Service import update completed!");
        $this->info("Total replacements: $updatedCount");
        $this->info("Files updated: $filesUpdated");
        
        // Show summary of replacements
        if ($filesUpdated > 0) {
            $this->newLine();
            $this->info("Applied replacements:");
            foreach ($this->replacements as $old => $new) {
                $this->line("  $old → $new");
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Get all PHP files in the app directory
     */
    protected function getPhpFiles(): array
    {
        return File::allFiles(app_path())
            ->filter(function ($file) {
                return $file->getExtension() === 'php';
            })
            ->map(function ($file) {
                return $file->getPathname();
            })
            ->toArray();
    }
}