<?php

namespace App\Providers;

use App\Models\Ad;
use App\Models\Notification;
use App\Repositories\Auth\AuthContract;
use App\Repositories\Auth\AuthRepository;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Pet\PetRepository;
use App\Repositories\Pet\PetRepositoryContract;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AuthContract::class, AuthRepository::class);
        $noticationRepository =  (new NotificationRepository())->setModel(new Notification());
        $this->app->instance(NotificationRepository::class, $noticationRepository);
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
