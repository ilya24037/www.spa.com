<?php

namespace App\Console\Commands\Cache;

use Illuminate\Console\Command;
use App\Infrastructure\Cache\Decorators\CachedAdRepository;
use App\Infrastructure\Cache\Decorators\CachedMasterRepository;
use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Service\Models\Service;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--type=all : Type of cache to warm (all, ads, masters, popular)}
                            {--force : Force cache refresh even if exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info('🔥 Starting cache warming...');
        
        if ($force) {
            $this->warn('Force mode: clearing existing cache first');
            $this->call('cache:clear');
        }

        switch ($type) {
            case 'ads':
                $this->warmAdsCache();
                break;
            case 'masters':
                $this->warmMastersCache();
                break;
            case 'popular':
                $this->warmPopularCache();
                break;
            case 'all':
            default:
                $this->warmAdsCache();
                $this->warmMastersCache();
                $this->warmPopularCache();
                break;
        }

        $this->info('✅ Cache warming completed!');
        return Command::SUCCESS;
    }

    /**
     * Прогрев кеша объявлений
     */
    protected function warmAdsCache(): void
    {
        $this->info('Warming ads cache...');
        
        $adRepository = app(CachedAdRepository::class);
        
        // Прогреваем популярные объявления
        $this->task('Popular ads', function () use ($adRepository) {
            $adRepository->getPopularCached();
        });
        
        // Прогреваем рекомендуемые объявления
        $this->task('Featured ads', function () use ($adRepository) {
            $adRepository->getFeaturedCached();
        });
        
        // Прогреваем основные категории
        $categories = Ad::distinct()->pluck('category')->take(10);
        foreach ($categories as $category) {
            $this->task("Category: {$category}", function () use ($adRepository, $category) {
                $adRepository->findByCategoryCached($category);
            });
        }
        
        $this->info('✓ Ads cache warmed');
    }

    /**
     * Прогрев кеша мастеров
     */
    protected function warmMastersCache(): void
    {
        $this->info('Warming masters cache...');
        
        $masterRepository = app(CachedMasterRepository::class);
        
        // Прогреваем топ мастеров
        $this->task('Top masters by rating', function () use ($masterRepository) {
            $masterRepository->getTopMastersCached('rating');
        });
        
        $this->task('Top masters by bookings', function () use ($masterRepository) {
            $masterRepository->getTopMastersCached('bookings_count');
        });
        
        // Прогреваем премиум и верифицированных
        $this->task('Premium masters', function () use ($masterRepository) {
            $masterRepository->getPremiumMastersCached();
        });
        
        $this->task('Verified masters', function () use ($masterRepository) {
            $masterRepository->getVerifiedMastersCached();
        });
        
        // Прогреваем по основным услугам
        $services = Service::popular()->take(5)->pluck('id');
        foreach ($services as $serviceId) {
            $this->task("Service ID: {$serviceId}", function () use ($masterRepository, $serviceId) {
                $masterRepository->findByServiceCached($serviceId);
            });
        }
        
        $this->info('✓ Masters cache warmed');
    }

    /**
     * Прогрев популярного контента
     */
    protected function warmPopularCache(): void
    {
        $this->info('Warming popular content cache...');
        
        $adRepository = app(CachedAdRepository::class);
        $masterRepository = app(CachedMasterRepository::class);
        
        // Топ 20 объявлений
        $this->task('Top 20 ads', function () use ($adRepository) {
            $topAds = Ad::active()
                ->orderBy('views_count', 'desc')
                ->take(20)
                ->pluck('id');
                
            foreach ($topAds as $adId) {
                $adRepository->findCached($adId);
            }
        });
        
        // Топ 20 мастеров
        $this->task('Top 20 masters', function () use ($masterRepository) {
            $topMasters = MasterProfile::active()
                ->orderBy('rating', 'desc')
                ->take(20)
                ->pluck('id');
                
            foreach ($topMasters as $masterId) {
                $masterRepository->findCached($masterId);
            }
        });
        
        $this->info('✓ Popular content cache warmed');
    }

    /**
     * Выполнить задачу с отображением статуса
     */
    protected function task(string $description, callable $task): void
    {
        $this->output->write("  - {$description}... ");
        
        try {
            $task();
            $this->output->writeln('<info>✓</info>');
        } catch (\Exception $e) {
            $this->output->writeln('<error>✗</error>');
            $this->error("    Error: {$e->getMessage()}");
        }
    }
}