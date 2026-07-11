<?php

namespace App\Providers;

use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // JSON_PRESERVE_ZERO_FRACTION garante que floats "redondos" (ex.: 150.0)
        // não sejam serializados como inteiros (150) — PHP's json_encode() faz
        // isso por padrão, o que quebra contratos de API que sempre devolvem
        // valores monetários como float (ex.: Budget::totalAmount, relatórios).
        $this->app->singleton(ResponseFactoryContract::class, function ($app) {
            return new class($app[ViewFactoryContract::class], $app['redirect']) extends ResponseFactory
            {
                public function json($data = [], $status = 200, array $headers = [], $options = 0)
                {
                    return parent::json($data, $status, $headers, $options | JSON_PRESERVE_ZERO_FRACTION);
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
