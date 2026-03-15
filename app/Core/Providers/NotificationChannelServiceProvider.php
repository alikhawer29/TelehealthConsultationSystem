<?php

namespace App\Core\Providers;

use App\Core\Channels\FirebaseChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class NotificationChannelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make(ChannelManager::class)->extend('firebase', function ($app) {
            return new FirebaseChannel($app->make(\Kreait\Firebase\Contract\Messaging::class));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
