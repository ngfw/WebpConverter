<?php

namespace Ngfw\WebpConverter;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;


class WebpConverterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webp_converter.php' => config_path('webp_converter.php'),
            ], 'webp_converter');
        }
        Blade::directive('webpConverter', function ($expression) {
            return "<?php echo app(\Ngfw\WebpConverter\WebpConverter::class)->load($expression)->convert(); ?>";
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/webp_converter.php', 'webp_converter');

        // Register the main class to use with the facade
        $this->app->singleton('webpConverter', function ($app) {
            $filesystem = $app->make(Filesystem::class);
            return new WebpConverter($filesystem);
        });
    }
}
