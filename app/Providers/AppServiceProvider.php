<?php

namespace App\Providers;

use App\Models\Ad;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::statement("SET time_zone = '+08:00'");
    }
}
