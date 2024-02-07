<?php

namespace Parsidev\Captcha\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Parsidev\Captcha\Captcha\Captcha;

class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/parsidev/captcha.php', 'parsidev.captcha');
        $this->loadViewsFrom(__DIR__ . '/../resources/views/vendor/parsidev', 'parsidev');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang/vendor/parsidev', 'parsidev');

        $this->publishes([__DIR__ . '/../config' => config_path()], 'parsidev-captcha-config');
        $this->publishes([__DIR__ . '/../resources/lang' => resource_path('lang')], 'parsidev-captcha-lang');
        $this->publishes([__DIR__ . '/../resources/views' => resource_path('views')], 'parsidev-captcha-views');

        $this->registerRoutes();
        $this->registerBladeDirectives();
        $this->registerValidator();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Captcha::class, static function (Application $app) {
            $config = $app['config']['parsidev']['captcha'];

            $storage   = $app->make($config['storage']);
            $generator = $app->make($config['generator']);
            $code      = $app->make($config['code']);

            return new Captcha($code, $storage, $generator, $config);
        });
    }

    /**
     * Register the blade directives
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        if (! class_exists('\Blade')) {
            return;
        }

        Blade::directive(config('parsidev.captcha.blade'), static function () {
            return '<?php echo Parsidev\Captcha\Facades\Captcha::getView() ?>';
        });
    }

    /**
     * Register captcha routes.
     */
    protected function registerRoutes()
    {
        $this->app['router']->group([
            'middleware' => config('parsidev.captcha.middleware', 'web'),
            'namespace'  => 'Parsidev\Captcha\Controllers',
            'as'         => 'parsidev.captcha.'
        ], static function ($router) {
            $router->get(config('parsidev.captcha.routes.image'), 'CaptchaController@image')->name('image');
            $router->get(config('parsidev.captcha.routes.image_tag'), 'CaptchaController@imageTag')->name('image.tag');
        });
    }

    /**
     * Register captcha validator.
     */
    protected function registerValidator()
    {
        Validator::extend(config('parsidev.captcha.validator'), function ($attribute, $value, $parameters, $validator) {
            return $this->app[Captcha::class]->validate($value);
        }, trans('parsidev::captcha.incorrect_code'));
    }
}
