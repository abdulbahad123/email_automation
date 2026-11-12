<?php

namespace Modules\AppChannelWhatsappUnofficial\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Core;

class AppChannelWhatsappUnofficialServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppChannelWhatsappUnofficial';

    protected string $nameLower = 'appchannelwhatsappunofficial';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->btnChannels();
        $this->registerPlanPermissions();
        $this->registerSubMenu();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function btnChannels(): void
    {
        if(get_option("whatsapp_unofficial_profile_status", 0) || request()->segment(1) == "admin"){
            \Channels::addChannel($this->name, [
                "name" => __("WhatsApp Unofficial"),
                "social_network" => "whatsapp_unofficial",
                "category" => "profile",
                "position" => 11
            ]);
        }
    }

    protected function registerPricingFeature(): void
    {
        \Pricing::add([
            [
                // Example top-level pricing limit shown outside Access Features.
                "sort"      => 120,
                "key"       => "whatsapp_message_per_month",
                "label"     => __("WhatsApp messages / month"),
                "check"     => true,
                "type"      => "limit",
                "raw"       => 0,
            ],
        ]);

        \Pricing::addSubFeatures([
            [
                "sort"      => 4101,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappprofileinfo",
                "label"     => __("Profile Info"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4102,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappreport",
                "label"     => __("Reports"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4103,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappchat",
                "label"     => __("Live Chat"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4104,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappbulk",
                "label"     => __("Bulk campaigns"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4105,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappaismartreply",
                "label"     => __("AI Smart Reply"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4106,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappautoreply",
                "label"     => __("Auto Reply"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4107,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappchatbot",
                "label"     => __("Chatbot"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4108,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappcontact",
                "label"     => __("Contacts"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4109,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappparticipantsexport",
                "label"     => __("Export participants"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4110,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "appwhatsappapi",
                "label"     => __("REST API"),
                "check"     => true,
                "type"      => "boolean",
                "raw"       => 0,
            ],
            [
                "sort"      => 4111,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "whatsapp_message_per_month",
                "label"     => __("Monthly WhatsApp messages"),
                "check"     => true,
                "type"      => "limit",
                "raw"       => 0,
            ],
            [
                "sort"      => 4112,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "whatsapp_chatbot_item_limit",
                "label"     => __("Chatbot item limit"),
                "check"     => true,
                "type"      => "limit",
                "raw"       => 0,
            ],
            [
                "sort"      => 4113,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "whatsapp_bulk_max_contact_group",
                "label"     => __("Maximum contact groups"),
                "check"     => true,
                "type"      => "limit",
                "raw"       => 0,
            ],
            [
                "sort"      => 4114,
                "parent"    => "features",
                "tab_id"    => 'whatsapp_unofficial',
                "tab_name"  => __("WhatsApp Unofficial"),
                "key"       => "whatsapp_bulk_max_phone_numbers",
                "label"     => __("Maximum phone numbers per contact group"),
                "check"     => true,
                "type"      => "limit",
                "raw"       => 0,
            ]
        ]);
    }

    public function registerSubMenu(): void
    {
        Core::addSubMenu("AdminAPIIntegration", [
            [
                "uri" => "admin/api-integration/whatsapp-unofficial",
                "name" => "WhatsApp Unofficial",
                "position" => 500000,
                "icon" => "fa-light fa-puzzle",
                "color" => "#5156ff"
            ],
        ]);
    }

    protected function registerPlanPermissions(): void
    {
        \Plan::addPermissions($this->name, [
            "sort" => 4100,
            "view" => "permissions",
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerPricingFeature();
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
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
