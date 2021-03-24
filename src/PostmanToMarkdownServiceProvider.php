<?php


namespace AndPHP\Postman;

use Illuminate\Support\ServiceProvider;

class PostmanToMarkdownServiceProvider extends ServiceProvider
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
            __DIR__.'/config/postman.php' => config_path('postman.php'),
        ]);
    }
}