<?php

namespace AlbertCht\InvisibleReCaptcha;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class InvisibleReCaptchaServiceProvider extends ServiceProvider
{
    /**
     * Boot the services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig();
        $this->app['validator']->extend('invcaptcha', function ($attribute, $value) {
            return $this->app['invcaptcha']->verifyResponse($value, $this->app['request']->getClientIp());
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('invcaptcha', function ($app) {
            return new InvisibleReCaptcha(
                $app['config']['invcaptcha.siteKey'],
                $app['config']['invcaptcha.secretKey'],
                $app['config']['invcaptcha.options']
            );
        });

        $this->app->afterResolving('blade.compiler', function () {
            $this->addBladeDirective($this->app['blade.compiler']);
        });
    }

    /**
     * Boot configure.
     *
     * @return void
     */
    protected function bootConfig()
    {
        $path = __DIR__.'/config/invcaptcha.php';

        $this->mergeConfigFrom($path, 'invcaptcha');

        if (function_exists('config_path')) {
            $this->publishes([$path => config_path('invcaptcha.php')]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['invcaptcha'];
    }

    /**
     * @param BladeCompiler $blade
     * @return void
     */
    public function addBladeDirective(BladeCompiler $blade)
    {
        $blade->directive('invcaptcha', function ($lang) {
            return "<?php echo app('invcaptcha')->render({$lang}); ?>";
        });
        $blade->directive('captchaPolyfill', function () {
            return "<?php echo app('invcaptcha')->renderPolyfill(); ?>";
        });
        $blade->directive('captchaHTML', function () {
            return "<?php echo app('invcaptcha')->renderCaptchaHTML(); ?>";
        });
        $blade->directive('captchaScripts', function ($lang) {
            return "<?php echo app('invcaptcha')->renderFooterJS({$lang}); ?>";
        });
    }
}
