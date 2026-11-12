<?php

namespace Modules\AdminMailSender\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AdminMailSenderServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AdminMailSender';

    protected string $nameLower = 'adminmailsender';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->applyMailConfigFromOptions();
        if (! class_exists('MailSender')) {
            class_alias(\Modules\AdminMailSender\Facades\MailSender::class, 'MailSender');
        }
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public static function applyMailConfigFromOptions()
    {
        // Protocol: default to smtp for reliable delivery
        $protocol = get_option('mail_protocol', 'smtp');
        $driver = $protocol == 'mail' ? 'sendmail' : 'smtp';

        $host = get_option('smtp_server') ?: env('MAIL_HOST', '');
        $port = get_option('smtp_port') ?: env('MAIL_PORT', 465);
        $user = get_option('smtp_username') ?: env('MAIL_USERNAME', '');
        $pass = get_option('smtp_password') ?: env('MAIL_PASSWORD', '');
        $encryption = get_option('smtp_encryption') ?: env('MAIL_ENCRYPTION', 'ssl');
        $enc = strtolower($encryption) == 'none' ? null : strtolower($encryption);
        $fromEmail = get_option('mail_sender_email') ?: env('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $fromName = get_option('mail_sender_name') ?: env('MAIL_FROM_NAME', 'WhatsApp Bulk');

        config([
            'mail.default'                  => $driver,
            'mail.mailers.smtp.host'        => $host,
            'mail.mailers.smtp.port'        => $port,
            'mail.mailers.smtp.username'    => $user,
            'mail.mailers.smtp.password'    => $pass,
            'mail.mailers.smtp.encryption'  => $enc,
            'mail.from.address'             => $fromEmail,
            'mail.from.name'                => $fromName,
        ]);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
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

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
