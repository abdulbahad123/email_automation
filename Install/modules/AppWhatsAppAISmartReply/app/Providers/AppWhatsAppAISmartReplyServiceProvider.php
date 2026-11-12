<?php

namespace Modules\AppWhatsAppAISmartReply\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppWhatsAppAISmartReplyServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppWhatsAppAISmartReply';
    protected string $nameLower = 'appwhatsappaismartreply';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void {}
    protected function registerCommandSchedules(): void {}

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

    private function syncSchema(): void
    {
        if (!Schema::hasTable('whatsapp_ai_smart_reply')) {
            Schema::create('whatsapp_ai_smart_reply', function (Blueprint $table) {
                $table->increments('id');
                $table->string('id_secure')->nullable()->unique();
                $table->integer('team_id')->nullable()->index();
                $table->string('instance_id')->nullable()->index();
                $table->mediumText('prompt')->nullable();
                $table->mediumText('fallback_caption')->nullable();
                $table->text('except')->nullable();
                $table->integer('delay')->default(1);
                $table->integer('send_to')->default(1);
                $table->integer('max_length')->default(120);
                $table->integer('sent')->default(0);
                $table->integer('failed')->default(0);
                $table->integer('status')->default(1);
                $table->integer('changed')->nullable();
                $table->integer('created')->nullable();
            });
        }

        if (Schema::hasTable('whatsapp_ai_smart_reply') && !Schema::hasColumn('whatsapp_ai_smart_reply', 'max_length')) {
            Schema::table('whatsapp_ai_smart_reply', function (Blueprint $table) {
                $table->integer('max_length')->default(120)->after('send_to');
            });
        }

        if (Schema::hasTable('whatsapp_ai_smart_reply') && !Schema::hasColumn('whatsapp_ai_smart_reply', 'fallback_caption')) {
            Schema::table('whatsapp_ai_smart_reply', function (Blueprint $table) {
                $table->mediumText('fallback_caption')->nullable()->after('prompt');
            });
        }
    }
}