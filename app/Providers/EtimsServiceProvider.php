<?php

namespace App\Providers;

use App\Services\Etims\EtimsClientInterface;
use App\Services\Etims\MockEtimsClient;
use App\Services\Etims\OscuEtimsClient;
use Illuminate\Support\ServiceProvider;

class EtimsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EtimsClientInterface::class, function () {
            return config('etims.driver') === 'oscu'
                ? new OscuEtimsClient()
                : new MockEtimsClient();
        });
    }
}