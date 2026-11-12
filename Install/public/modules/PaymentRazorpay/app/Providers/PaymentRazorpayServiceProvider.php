<?php

namespace Modules\PaymentRazorpay\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class PaymentRazorpayServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'PaymentRazorpay';
    protected string $nameLower = 'paymentrazorpay';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->ensureDefaultOptions();
        $this->regiterPaymentGateway();
    }

    public function ensureDefaultOptions(): void
    {
        if (get_option("razorpay_key_id") === null || get_option("razorpay_key_id") === "") {
            update_option("razorpay_key_id", "rzp_test_T9UaATIMf1qeO8");
        }
        if (get_option("razorpay_key_secret") === null || get_option("razorpay_key_secret") === "") {
            update_option("razorpay_key_secret", "BQ9Z865NgRQrrIMCusfzmskZ");
        }
        if (get_option("razorpay_status") === null) {
            update_option("razorpay_status", 1);
        }
    }

    public function regiterPaymentGateway(): void
    {
        if (request()->segment(1) == "admin" || get_option("razorpay_status", 1)) {
            \Payment::addPaymentGateway($this->name, [
                "type" => 1,
                "logo_path" => "resources/assets/logo.png",
                "modal" => "razorpayConfigurationModal",
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'resources/lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'resources/lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, 'config/config.php');
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path($this->nameLower.'.php'),
            ], 'config');
            $this->mergeConfigFrom($configPath, $this->nameLower);
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
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
