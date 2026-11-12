<?php

namespace Modules\AppEmailBulk\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\AppEmailBulk\Console\ProcessEmailCampaignsCommand;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppEmailBulkServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppEmailBulk';
    protected string $nameLower = 'appemailbulk';

    public function boot(): void
    {
        $this->registerSubMenu();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerPlanPermissions();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        if (class_exists('AppDashboard')) {
            \AppDashboard::registerDashboardItem(function () {
                return view('appemailbulk::partials.dashboard-item')->render();
            }, 15000, fn () => auth()->check() && (
                session('login_as', 'client') !== 'client'
                || (
                    \Access::canAccess('appemailautomation', false)
                    && \Access::canAccess('appemailsend', false)
                )
            ));
        }

        if (class_exists('CronService')) {
            try {
                \CronService::addCron($this->name, [
                    'email:process-campaigns' => url('cron/email-campaigns?key=' . get_option('cron_key', '')),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('AppEmailBulk CronService registration skipped: ' . $e->getMessage());
            }
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->registerPricingFeature();
    }

    protected function registerSubMenu(): void
    {
        if (class_exists('Core')) {
            \Core::addSubMenu($this->name, [
                [
                    'uri' => 'app/email/send',
                    'name' => 'Send Single Email',
                    'permission' => 'appemailsend',
                    'position' => 1000,
                    'icon' => 'fa-light fa-paper-plane',
                    'color' => '#6d5dfc',
                ],
            ]);
        }
    }

    protected function registerPlanPermissions(): void
    {
        if (class_exists('Plan')) {
            \Plan::addPermissions($this->name, [
                'sort' => 4200,
                'id' => '',
                'key' => 'appemailbulk',
                'module_name' => __('Email Automation'),
                'view' => 'permissions',
            ]);
        }
    }

    protected function registerPricingFeature(): void
    {
        if (class_exists('Pricing')) {
            $features = [
                ['key' => 'appemailbulk', 'label' => __('Bulk Email'), 'sort' => 60],
                ['key' => 'appemailsend', 'label' => __('Send Single Email'), 'sort' => 55],
                ['key' => 'appemailcontact', 'label' => __('Email Contacts'), 'sort' => 50],
                ['key' => 'appemailsmtp', 'label' => __('SMTP Accounts'), 'sort' => 45],
            ];

            foreach ($features as $feature) {
                \Pricing::addSubFeatures([
                    'sort' => $feature['sort'],
                    'parent' => 'features',
                    'tab_id' => 'email_automation',
                    'tab_name' => __('Email Automation'),
                    'key' => $feature['key'],
                    'label' => $feature['label'],
                    'type' => 'boolean',
                ]);
            }
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            ProcessEmailCampaignsCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('email:process-campaigns')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower . '.' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }
}

