<?php

namespace App\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setupApiServer();
    }

    /**
     * function setupApiServer
     *
     * TODO: move to an ServiceProvider
     *
     * @return void
     */
    public function setupApiServer(): void
    {
        $apiBaseUrl = config('api-server.base_url');

        if (!$apiBaseUrl || !filter_var($apiBaseUrl, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid 'api-server.base_url'", 1);
        }

        $headers = (array) config('api-server.headers', []);

        // https://laravel.com/docs/10.x/http-client#macros
        Http::macro(
            'apiServer',
            fn (array $addHeaders = []) => Http::withHeaders(
                \array_merge(
                    $headers,
                    $addHeaders
                )
            )->baseUrl($apiBaseUrl)
        );
    }
}
