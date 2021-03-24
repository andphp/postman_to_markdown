<?php


namespace AndPHP\Postman;

use Illuminate\Support\ServiceProvider;

class PostmanToMarkdownServiceProvider extends ServiceProvide
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/docs.php' => config_path('docs.php'),
        ]);
    }
}